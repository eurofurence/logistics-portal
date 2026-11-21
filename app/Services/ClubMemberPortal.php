<?php

namespace App\Services;

use App\Mail\ClubMemberPortalLink;
use App\Models\ClubMember;
use App\Models\ClubMemberAccount;
use App\Models\ClubMemberToken;
use App\Models\User;
use App\Settings\GeneralSettings;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;
use Throwable;

class ClubMemberPortal
{
    public function __construct(private UpdateClubMember $updateMember) {}

    public function authenticate(string $loginId, #[SensitiveParameter] string $password): ?ClubMemberAccount
    {
        $candidate = $this->findAccountBySonaName($loginId);
        $guard = Auth::guard(ClubMemberAccount::GUARD);

        return DB::transaction(function () use ($candidate, $guard, $loginId, $password): ?ClubMemberAccount {
            $member = $candidate === null ? null : ClubMember::query()->lockForUpdate()->find($candidate->club_member_id);

            if ($candidate === null || ! $guard->attemptWhen(['login_id' => $candidate->login_id, 'password' => $password],
                fn (ClubMemberAccount $account): bool => $member?->canUsePortal()
                    && Str::lower(trim($member->sona_name)) === Str::lower(trim($loginId))
                    && $member->id === $account->club_member_id)) {
                return null;
            }

            return $guard->user();
        });
    }

    public function invite(ClubMember $record, User $actor): bool
    {
        return DB::transaction(function () use ($record, $actor): bool {
            $member = ClubMember::withTrashed()->lockForUpdate()->findOrFail($record->id);
            Gate::forUser($actor)->authorize('sendPortalInvitation', $member);
            abort_unless($member->canUsePortal(), 403);

            $account = $member->portalAccount()->first();

            if (blank($member->email)) {
                throw ValidationException::withMessages(['invitation' => __('member-portal.invitation_unavailable')]);
            }

            if ($account?->password !== null) {
                try {
                    Mail::to($member->email)->locale(app(GeneralSettings::class)->default_locale)
                        ->send(new ClubMemberPortalLink('access', route('member-portal.login'), $member->sona_name));

                    return true;
                } catch (Throwable $exception) {
                    Log::warning('Club member access email delivery failed', ['exception' => $exception::class]);

                    return false;
                }
            }

            $account ??= $member->portalAccount()->create(['login_id' => (string) Str::uuid()]);
            [$token, $secret] = $this->issue($member, $account, ClubMemberToken::ACTIVATION, $member->email, 48 * 60);

            return $this->send($token, $secret, $member->sona_name);
        });
    }

    public function resetAccess(ClubMember $record, User $actor): void
    {
        DB::transaction(function () use ($record, $actor): void {
            $member = ClubMember::withTrashed()->lockForUpdate()->findOrFail($record->id);
            Gate::forUser($actor)->authorize('sendPortalInvitation', $member);
            abort_unless($member->canUsePortal(), 403);
            $account = $member->portalAccount()->lockForUpdate()->first();

            if ($account?->password === null || blank($member->email)) {
                throw ValidationException::withMessages(['invitation' => __('member-portal.invitation_unavailable')]);
            }

            $account->tokens()->delete();
            $account->password = null;
            $account->session_version++;
            $account->save();
            [$token, $secret] = $this->issue($member, $account, ClubMemberToken::ACTIVATION, $member->email, 48 * 60);

            if (! $this->send($token, $secret, $member->sona_name)) {
                throw ValidationException::withMessages(['invitation' => __('members.mail_failed')]);
            }
        });
    }

    public function requestReset(string $loginId): void
    {
        $candidate = $this->findAccountBySonaName($loginId);

        if ($candidate === null) {
            return;
        }

        $issued = DB::transaction(function () use ($candidate, $loginId): ?array {
            $member = ClubMember::query()->lockForUpdate()->find($candidate->club_member_id);
            $account = ClubMemberAccount::query()->lockForUpdate()->find($candidate->id);

            if (! $member?->canUsePortal() || $account?->password === null || blank($member->email)
                || Str::lower(trim($member->sona_name)) !== Str::lower(trim($loginId))) {
                return null;
            }

            return $this->issue($member, $account, ClubMemberToken::RESET, $member->email, 60);
        });

        if ($issued !== null) {
            $this->send(...$issued);
        }
    }

    private function findAccountBySonaName(string $sonaName): ?ClubMemberAccount
    {
        $normalizedName = Str::lower(trim($sonaName));
        $members = ClubMember::query()->has('portalAccount')->lazyById()
            ->filter(fn (ClubMember $member): bool => $member->canUsePortal()
                && Str::lower(trim($member->sona_name)) === $normalizedName)
            ->take(2)->collect();

        return $members->count() === 1 ? $members->first()->portalAccount : null;
    }

    public function setPassword(string $purpose, #[SensitiveParameter] string $secret, #[SensitiveParameter] string $password): ClubMemberAccount
    {
        return $this->redeem($purpose, $secret, function (ClubMember $member, ClubMemberAccount $account) use ($purpose, $password): ClubMemberAccount {
            if (($purpose === ClubMemberToken::ACTIVATION) !== ($account->password === null)) {
                $this->invalidToken();
            }

            return $this->storePassword($account, $password);
        });
    }

    public function changePassword(ClubMemberAccount $account, #[SensitiveParameter] string $currentPassword, #[SensitiveParameter] string $password): ClubMemberAccount
    {
        return $this->withActiveAccount($account, function (ClubMember $member, ClubMemberAccount $lockedAccount) use ($currentPassword, $password): ClubMemberAccount {
            $this->checkPassword($lockedAccount, $currentPassword);
            $lockedAccount->tokens()->where('purpose', ClubMemberToken::RESET)->delete();

            return $this->storePassword($lockedAccount, $password);
        });
    }

    /** @param array<string, string|null> $data */
    public function updateProfile(ClubMemberAccount $account, array $data): void
    {
        $this->withActiveAccount($account, fn (ClubMember $member) => $this->updateMember->fromMember($member, $data));
    }

    public function requestEmailChange(ClubMemberAccount $account, string $email, #[SensitiveParameter] string $password): bool
    {
        $issued = $this->withActiveAccount($account, function (ClubMember $member, ClubMemberAccount $lockedAccount) use ($email, $password): array {
            $this->checkPassword($lockedAccount, $password);

            if (Str::lower($member->email) === Str::lower($email)) {
                throw ValidationException::withMessages(['email' => __('member-portal.email_unchanged')]);
            }

            return $this->issue($member, $lockedAccount, ClubMemberToken::EMAIL_CHANGE, $email, 24 * 60);
        });

        return $this->send(...$issued);
    }

    public function confirmEmail(#[SensitiveParameter] string $secret): void
    {
        $this->redeem(ClubMemberToken::EMAIL_CHANGE, $secret, function (ClubMember $member, ClubMemberAccount $account, ClubMemberToken $token): void {
            $this->updateMember->confirmEmail($member, $account, $token->destination);
        });
    }

    private function withActiveAccount(ClubMemberAccount $account, Closure $operation): mixed
    {
        return DB::transaction(function () use ($account, $operation): mixed {
            $member = ClubMember::query()->lockForUpdate()->find($account->club_member_id);
            $lockedAccount = ClubMemberAccount::query()->lockForUpdate()->find($account->id);

            abort_unless($member?->canUsePortal() && $lockedAccount?->session_version === $account->session_version, 403);

            return $operation($member, $lockedAccount);
        });
    }

    /** @return array{ClubMemberToken, string} */
    private function issue(ClubMember $member, ClubMemberAccount $account, string $purpose, string $destination, int $minutes): array
    {
        $secret = Str::random(64);
        $token = $account->tokens()->updateOrCreate(['purpose' => $purpose], [
            'digest' => hash('sha256', $secret),
            'expires_at' => now()->addMinutes($minutes),
            'current_email' => $member->email,
            'destination' => $destination,
        ]);

        return [$token, $secret];
    }

    private function redeem(string $purpose, #[SensitiveParameter] string $secret, Closure $operation): mixed
    {
        $digest = hash('sha256', $secret);
        $candidate = ClubMemberToken::query()->where('digest', $digest)->where('purpose', $purpose)->first();
        $account = $candidate?->account;

        if ($account === null) {
            $this->invalidToken();
        }

        return DB::transaction(function () use ($account, $candidate, $digest, $operation): mixed {
            $member = ClubMember::query()->lockForUpdate()->find($account->club_member_id);
            $lockedAccount = ClubMemberAccount::query()->lockForUpdate()->find($account->id);
            $token = ClubMemberToken::query()->lockForUpdate()->find($candidate->id);

            if (! $member?->canUsePortal() || ! $lockedAccount || ! $token
                || ! hash_equals($token->digest, $digest)
                || ! $token->expires_at->isFuture() || $token->current_email !== $member->email) {
                $this->invalidToken();
            }

            $result = $operation($member, $lockedAccount, $token);
            $token->delete();

            return $result;
        });
    }

    private function storePassword(ClubMemberAccount $account, #[SensitiveParameter] string $password): ClubMemberAccount
    {
        $account->password = Hash::make($password);
        $account->session_version++;
        $account->save();

        return $account;
    }

    private function checkPassword(ClubMemberAccount $account, #[SensitiveParameter] string $password): void
    {
        if (! Hash::check($password, $account->password)) {
            throw ValidationException::withMessages(['current_password' => __('member-portal.invalid_current_password')]);
        }
    }

    private function invalidToken(): never
    {
        throw ValidationException::withMessages(['token' => __('member-portal.invalid_token')]);
    }

    private function send(ClubMemberToken $token, #[SensitiveParameter] string $secret, ?string $loginId = null): bool
    {
        $url = route('member-portal.'.$token->purpose).'#token='.$secret;

        try {
            Mail::to($token->destination)->locale(app(GeneralSettings::class)->default_locale)
                ->send(new ClubMemberPortalLink($token->purpose, $url, $loginId));

            return true;
        } catch (Throwable $exception) {
            ClubMemberToken::query()->whereKey($token->id)->where('digest', $token->digest)->delete();

            Log::warning('Club member portal email delivery failed', [
                'purpose' => $token->purpose,
                'exception' => $exception::class,
            ]);

            return false;
        }
    }
}

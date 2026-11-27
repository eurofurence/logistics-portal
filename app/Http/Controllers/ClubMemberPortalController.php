<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureClubMemberAccess;
use App\Jobs\SendClubMemberResetLink;
use App\Models\ClubMember;
use App\Models\ClubMemberAccount;
use App\Models\ClubMemberToken;
use App\Services\ApplicationTime;
use App\Services\ClubMemberPortal;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ClubMemberPortalController extends Controller
{
    public function __construct(private ClubMemberPortal $portal) {}

    public function showLogin(): Response
    {
        return Inertia::render('ClubMembers/Access', ['mode' => 'login']);
    }

    public function updateLocale(Request $request): JsonResponse
    {
        $data = $request->validate(['locale' => ['required', 'string', Rule::in(array_keys(config('app.available_locales')))]]);
        $request->session()->put('member_portal_locale', $data['locale']);

        return response()->json(['locale' => $data['locale']]);
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login_id' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', $this->passwordLength()],
        ]);
        $account = $this->portal->authenticate(Str::lower($data['login_id']), $data['password']);

        if ($account === null) {
            throw ValidationException::withMessages(['login_id' => __('member-portal.invalid_credentials')]);
        }

        $request->session()->put(ClubMemberAccount::SESSION_VERSION, $account->session_version);

        return to_route('member-portal.profile');
    }

    public function showForgotPassword(): Response
    {
        return Inertia::render('ClubMembers/Access', ['mode' => 'forgot']);
    }

    public function forgotPassword(Request $request): RedirectResponse
    {
        $data = $request->validate(['login_id' => ['required', 'string', 'max:255']]);
        SendClubMemberResetLink::dispatch(Str::lower($data['login_id']));

        return to_route('member-portal.forgot-password')->with('member_portal_status', __('member-portal.reset_requested'));
    }

    public function showTokenForm(Request $request): Response
    {
        return Inertia::render('ClubMembers/Access', ['mode' => $request->route('purpose')]);
    }

    public function redeemToken(Request $request): HttpResponse
    {
        $purpose = $request->route('purpose');
        $tokenRules = ['required', 'string', 'size:64', 'alpha_num:ascii'];

        if ($purpose === ClubMemberToken::EMAIL_CHANGE) {
            $data = $request->validate(['token' => $tokenRules]);
            $this->portal->confirmEmail($data['token']);

            return to_route(Auth::guard(ClubMemberAccount::GUARD)->check() ? 'member-portal.profile' : 'member-portal.login')
                ->with('member_portal_status', __('member-portal.email_updated'));
        }

        $data = $request->validate(['token' => $tokenRules, 'password' => $this->newPasswordRules()]);
        $account = $this->portal->setPassword($purpose, $data['token'], $data['password']);
        $guard = Auth::guard(ClubMemberAccount::GUARD);

        if ($purpose === ClubMemberToken::ACTIVATION) {
            $guard->login($account);
            $request->session()->put(ClubMemberAccount::SESSION_VERSION, $account->session_version);

            return Inertia::location(route('member-portal.profile', absolute: false));
        }

        if ($guard->id() === $account->id) {
            EnsureClubMemberAccess::forget($request);
        }

        return to_route('member-portal.login')->with('member_portal_status', __('member-portal.password_reset'));
    }

    public function showProfile(): Response
    {
        $account = $this->account();
        $member = $account->member;
        $profile = $member->only([...ClubMember::PROFILE_FIELDS, 'email', 'joined_at', 'left_at']);

        foreach (ClubMember::DATE_FIELDS as $field) {
            $profile[$field] = $profile[$field]?->toDateString();
        }

        $pendingEmail = $account->tokens()->where('purpose', ClubMemberToken::EMAIL_CHANGE)->where('expires_at', '>', now())->first();
        $countries = $this->countries();
        if ($member->country !== null) {
            $countries[$member->country] ??= $member->country;
        }
        asort($countries, SORT_NATURAL | SORT_FLAG_CASE);

        return Inertia::render('ClubMembers/Profile', [
            'profile' => $profile,
            'loginId' => $member->sona_name,
            'membershipStatus' => __('members.'.$member->membershipStatus()),
            'pendingEmail' => $pendingEmail?->destination,
            'countries' => $countries,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $account = $this->account();
        $data = $request->validate([
            'sona_name' => ['sometimes', 'required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', Rule::in([...array_keys($this->countries()), $account->member->country])],
            'phone' => ['nullable', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.ApplicationTime::now()->toDateString()],
        ]);
        $this->portal->updateProfile($account, $data);

        return to_route('member-portal.profile')->with('member_portal_status', __('member-portal.profile_saved'));
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'current_password' => ['required', 'string', $this->passwordLength()],
        ]);

        if (! $this->portal->requestEmailChange($this->account(), $data['email'], $data['current_password'])) {
            throw ValidationException::withMessages(['email' => __('members.mail_failed')]);
        }

        return to_route('member-portal.profile')->with('member_portal_status', __('member-portal.email_sent'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string', $this->passwordLength()],
            'password' => $this->newPasswordRules(),
        ]);
        $account = $this->portal->changePassword($this->account(), $data['current_password'], $data['password']);
        $request->session()->put(ClubMemberAccount::SESSION_VERSION, $account->session_version);
        $request->session()->regenerate(true);

        return to_route('member-portal.profile')->with('member_portal_status', __('member-portal.password_saved'));
    }

    public function logout(Request $request): HttpResponse
    {
        EnsureClubMemberAccess::forget($request);

        return Inertia::location(route('member-portal.login', absolute: false));
    }

    private function account(): ClubMemberAccount
    {
        return Auth::guard(ClubMemberAccount::GUARD)->user();
    }

    /** @return array<string, string> */
    private function countries(): array
    {
        return __('filament-country-field::country');
    }

    /** @return list<string|Password|Closure> */
    private function newPasswordRules(): array
    {
        return ['required', 'string', 'confirmed', Password::min(12), $this->passwordLength()];
    }

    private function passwordLength(): Closure
    {
        return function (string $attribute, mixed $password, Closure $fail): void {
            if (is_string($password) && (strlen($password) > 72 || str_contains($password, "\0"))) {
                $fail(__('member-portal.password_length'));
            }
        };
    }
}

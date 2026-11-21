<?php

namespace App\Services;

use App\Models\ClubMember;
use App\Models\ClubMemberAccount;
use App\Models\ClubMemberToken;
use App\Models\User;
use App\Notifications\ClubMemberProfileChanged;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdateClubMember
{
    /** @param array<string, string|null> $data */
    public function fromStaff(ClubMember $record, array $data, User $actor): ClubMember
    {
        DB::transaction(function () use ($record, $data, $actor): void {
            $member = ClubMember::query()->lockForUpdate()->findOrFail($record->id);
            Gate::forUser($actor)->authorize('update', $member);
            $changed = $this->save($member, $data, $actor);

            if (in_array('email', $changed, true)) {
                $member->portalAccount?->tokens()->delete();
            }

            if (array_intersect(['joined_at', 'left_at'], $changed)) {
                $member->portalAccount()->increment('session_version');

                if (! $member->canUsePortal()) {
                    $member->portalAccount?->tokens()->delete();
                }
            }
        });

        return $record->refresh();
    }

    /** @param array<string, string|null> $data */
    public function fromMember(ClubMember $member, array $data): void
    {
        if (isset($data['birth_date']) && $data['birth_date'] > $member->joined_at->toDateString()) {
            throw ValidationException::withMessages(['birth_date' => __('member-portal.birth_after_joining')]);
        }

        $this->save($member, array_intersect_key($data, array_flip(ClubMember::PROFILE_FIELDS)), null);
    }

    public function confirmEmail(ClubMember $member, ClubMemberAccount $account, string $email): void
    {
        $this->save($member, ['email' => $email], null);
        $account->tokens()->where('purpose', ClubMemberToken::RESET)->delete();
    }

    /**
     * @param  array<string, string|null>  $data
     * @return list<string>
     */
    private function save(ClubMember $member, array $data, ?User $actor): array
    {
        $changes = [];

        foreach (array_intersect_key($data, array_flip($member->getFillable())) as $field => $value) {
            $previous = $member->{$field};
            if (in_array($field, ClubMember::DATE_FIELDS, true)) {
                $previous = $previous?->toDateString();
                $value = $value === null ? null : Carbon::parse($value)->toDateString();
            }

            if ($previous !== $value) {
                $member->{$field} = $value;
                $changes[] = [
                    'field' => $field,
                    'previous_value' => $previous,
                    'new_value' => $value,
                    'staff_id' => $actor?->id,
                    'changed_by_member' => $actor === null,
                ];
            }
        }

        if ($changes === []) {
            return [];
        }

        $member->save();
        $member->changes()->createMany($changes);
        $fields = array_column($changes, 'field');

        if ($actor === null) {
            $memberId = $member->id;

            DB::afterCommit(function () use ($memberId, $fields): void {
                $notification = (new ClubMemberProfileChanged($memberId, $fields))->locale(app()->getLocale());

                User::query()
                    ->where('locked', false)
                    ->where(fn (Builder $query): Builder => $query
                        ->permission('update-ClubMember')
                        ->orWhereHas('roles', fn (Builder $query): Builder => $query->where('name', 'Master')->where('guard_name', 'web')))
                    ->with(['roles.permissions', 'permissions'])
                    ->eachById(function (User $user) use ($notification): void {
                        if ($notification->shouldSend($user, 'database')) {
                            $user->notify($notification);
                        }
                    });
            });
        }

        return $fields;
    }
}

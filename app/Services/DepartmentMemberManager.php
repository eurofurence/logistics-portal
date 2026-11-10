<?php

namespace App\Services;

use App\Models\Department;
use App\Models\DepartmentMember;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DepartmentMemberManager
{
    public function can(User $actor, int $departmentId, string $operation, bool $admin = false): bool
    {
        if (! Department::query()->whereKey($departmentId)->exists()) {
            return false;
        }
        if ($admin) {
            $ability = match ($operation) {
                'view' => 'view-any-DepartmentMember',
                'add' => 'create-DepartmentMember',
                'update' => 'update-DepartmentMember',
                'remove' => 'delete-DepartmentMember',
            };

            return Gate::forUser($actor)->allows('access-adminpanel') && Gate::forUser($actor)->allows($ability);
        }
        $permissions = ['view-department-members'];
        if ($operation !== 'view') {
            $permissions[] = match ($operation) {
                'add' => 'add-department-members',
                'update' => 'update-department-member-roles',
                'remove' => 'remove-department-members',
            };
        }
        foreach ($permissions as $permission) {
            if (! $actor->departmentMemberships()->where('department_id', $departmentId)
                ->whereHas('role.permissions', fn (Builder $query) => $query->where('name', $permission)->where('guard_name', 'web'))->exists()) {
                return false;
            }
        }

        return true;
    }

    public function departments(User $actor): Builder
    {
        return Department::query()->whereHas('members', fn (Builder $query) => $query->where('user_id', $actor->id)
            ->whereHas('role.permissions', fn (Builder $permissions) => $permissions
                ->where('name', 'view-department-members')->where('guard_name', 'web')));
    }

    public function members(int $departmentId): Builder
    {
        return User::query()->whereHas('departmentMemberships', fn (Builder $query) => $query->where('department_id', $departmentId))
            ->with(['departmentMemberships' => fn ($query) => $query->where('department_id', $departmentId)->with('role')]);
    }

    public function roles(bool $admin = false): Builder
    {
        return Role::query()->where('guard_name', 'web')->when(! $admin, fn (Builder $query) => $query
            ->where('assignable_in_app', true)->where('id', '<>', 1)->where('name', '<>', 'Master'))->orderBy('name');
    }

    /** @param array<int, int|string> $roleIds */
    public function save(User $actor, int $departmentId, int $userId, array $roleIds, bool $adding = false, bool $admin = false): void
    {
        DB::transaction(function () use ($actor, $departmentId, $userId, $roleIds, $adding, $admin): void {
            $this->lockUsers($actor, $userId);
            abort_unless($this->can($actor, $departmentId, $adding ? 'add' : 'update', $admin), 403);
            $member = User::query()->findOrFail($userId);
            abort_if($adding && $member->name === 'System', 403);
            $memberships = DepartmentMember::query()->where('department_id', $departmentId)->where('user_id', $userId)->lockForUpdate()->get();
            if ($adding && $memberships->isNotEmpty()) {
                throw ValidationException::withMessages(['user_id' => __('department_members.already_member')]);
            }
            abort_if(! $adding && $memberships->isEmpty(), 404);
            Validator::make(['roles' => $roleIds], ['roles' => ['array'], 'roles.*' => ['required', 'integer', 'distinct']])->validate();
            $selected = $this->roles($admin)->whereIn('id', $roleIds)->lockForUpdate()->get();
            if ($selected->count() !== count($roleIds)) {
                throw ValidationException::withMessages(['roles' => __('department_members.invalid_roles')]);
            }
            $protected = $memberships->filter(fn (DepartmentMember $membership): bool => ! $admin && $membership->role !== null && ! $membership->role->canBeAssignedInApp());
            $desiredIds = $selected->modelKeys();
            $existingIds = $memberships->pluck('role_id')->filter()->all();
            foreach ($memberships as $membership) {
                if ($membership->role_id === null && $desiredIds === [] && $protected->isEmpty()) {
                    continue;
                }
                if (! $protected->contains('id', $membership->id) && ! in_array($membership->role_id, $desiredIds, true)) {
                    $membership->delete();
                }
            }
            foreach (array_diff($desiredIds, $existingIds) as $roleId) {
                DepartmentMember::create(['department_id' => $departmentId, 'user_id' => $userId, 'role_id' => $roleId]);
            }
            if (! DepartmentMember::query()->where('department_id', $departmentId)->where('user_id', $userId)->exists()) {
                DepartmentMember::create(['department_id' => $departmentId, 'user_id' => $userId, 'role_id' => null]);
            }
            $member->incrementPermissionCacheVersion();
        });
    }

    public function remove(User $actor, int $departmentId, int $userId, bool $admin = false): void
    {
        DB::transaction(function () use ($actor, $departmentId, $userId, $admin): void {
            $this->lockUsers($actor, $userId);
            abort_unless($this->can($actor, $departmentId, 'remove', $admin), 403);
            $memberships = DepartmentMember::query()->where('department_id', $departmentId)->where('user_id', $userId)->with('role')->lockForUpdate()->get();
            abort_if($memberships->isEmpty(), 404);
            if (! $admin && $memberships->contains(fn (DepartmentMember $membership): bool => $membership->role?->isMaster() ?? false)) {
                throw ValidationException::withMessages(['roles' => __('department_members.master_protected')]);
            }
            foreach ($memberships as $membership) {
                $membership->delete();
            }
        });
    }

    private function lockUsers(User $actor, int $userId): void
    {
        User::query()->whereIn('id', [$actor->id, $userId])->orderBy('id')->lockForUpdate()->get();
    }
}

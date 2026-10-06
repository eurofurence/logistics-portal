<?php

namespace App\Policies;

use App\Models\ClubMember;
use App\Models\User;

class ClubMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-ClubMember') && $user->checkPermissionTo('view-ClubMember');
    }

    public function view(User $user, ClubMember $member): bool
    {
        return $user->checkPermissionTo('view-ClubMember');
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user) && $user->checkPermissionTo('create-ClubMember');
    }

    public function update(User $user, ClubMember $member): bool
    {
        return ! $member->trashed() && $this->view($user, $member) && $user->checkPermissionTo('update-ClubMember');
    }

    public function delete(User $user, ClubMember $member): bool
    {
        return ! $member->trashed() && $this->view($user, $member) && $user->checkPermissionTo('delete-ClubMember');
    }

    public function restore(User $user, ClubMember $member): bool
    {
        return $member->trashed() && $this->view($user, $member) && $user->checkPermissionTo('restore-ClubMember');
    }

    public function sendEmail(User $user, ClubMember $member): bool
    {
        return ! $member->trashed() && $this->view($user, $member) && $user->checkPermissionTo('send-email-ClubMember');
    }

    public function manageFiles(User $user, ClubMember $member): bool
    {
        return ! $member->trashed() && $this->view($user, $member) && $user->checkPermissionTo('manage-files-ClubMember');
    }

    public function forceDelete(User $user, ClubMember $member): bool
    {
        return false;
    }
}

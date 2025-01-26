<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\PdfDownload;
use App\Models\User;

class PdfDownloadPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any-PdfDownload');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PdfDownload $pdfdownload): bool
    {
        return $user->checkPermissionTo('view-PdfDownload');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create-PdfDownload');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PdfDownload $pdfdownload): bool
    {
        return $user->checkPermissionTo('update-PdfDownload');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PdfDownload $pdfdownload): bool
    {
        return $user->checkPermissionTo('delete-PdfDownload');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PdfDownload $pdfdownload): bool
    {
        return $user->checkPermissionTo('restore-PdfDownload');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PdfDownload $pdfdownload): bool
    {
        return $user->checkPermissionTo('force-delete-PdfDownload');
    }
}

<?php

namespace App\Filament\App\Resources\ClubMembers\Actions;

use App\Models\ClubMember;
use App\Services\ClubMemberPortal;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class SendClubMemberInvitation
{
    public static function make(): Action
    {
        return Action::make('sendPortalInvitation')
            ->label(fn (ClubMember $record): string => __($record->portalAccount?->password === null
                ? 'member-portal.send_invitation' : 'member-portal.resend_access'))
            ->icon('heroicon-o-envelope')
            ->authorize('sendPortalInvitation')
            ->visible(fn (ClubMember $record): bool => $record->canUsePortal())
            ->action(function (ClubMember $record, ClubMemberPortal $portal): void {
                try {
                    $sent = $portal->invite($record, auth()->user());
                    $record->unsetRelation('portalAccount');
                } catch (ValidationException $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();

                    return;
                }

                if ($sent) {
                    Notification::make()->title(__('members.mail_sent'))->success()->send();

                    return;
                }

                Notification::make()->title(__('members.mail_failed'))->danger()->send();
            });
    }

    public static function makeReset(): Action
    {
        return Action::make('resetPortalAccess')
            ->label(__('member-portal.reset_access'))
            ->icon('heroicon-o-key')
            ->color('danger')
            ->authorize('sendPortalInvitation')
            ->visible(fn (ClubMember $record): bool => $record->canUsePortal() && $record->portalAccount?->password !== null)
            ->requiresConfirmation()
            ->modalDescription(__('member-portal.reset_access_description'))
            ->action(function (ClubMember $record, ClubMemberPortal $portal): void {
                try {
                    $portal->resetAccess($record, auth()->user());
                    $record->unsetRelation('portalAccount');
                } catch (ValidationException $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title(__('member-portal.access_reset'))->success()->send();
            });
    }
}

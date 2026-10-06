<?php

namespace App\Filament\App\Resources\ClubMembers\Pages;

use App\Filament\App\Resources\ClubMembers\Actions\SendClubMemberEmail;
use App\Filament\App\Resources\ClubMembers\ClubMemberResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditClubMember extends EditRecord
{
    protected static string $resource = ClubMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->icon('heroicon-o-eye'),
            DeleteAction::make()->icon('heroicon-o-trash'),
            SendClubMemberEmail::make(),
            RestoreAction::make()->icon('heroicon-o-arrow-uturn-left'),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->icon('heroicon-o-check');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->icon('heroicon-o-x-mark');
    }
}

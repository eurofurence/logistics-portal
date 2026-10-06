<?php

namespace App\Filament\App\Resources\ClubMembers\Pages;

use App\Filament\App\Resources\ClubMembers\Actions\SendClubMemberEmail;
use App\Filament\App\Resources\ClubMembers\ClubMemberResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;

class ViewClubMember extends ViewRecord
{
    protected static string $resource = ClubMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->icon('heroicon-o-pencil-square'),
            SendClubMemberEmail::make(),
            DeleteAction::make()->icon('heroicon-o-trash'),
            RestoreAction::make()->icon('heroicon-o-arrow-uturn-left'),
        ];
    }
}

<?php

namespace App\Filament\App\Resources\ClubMembers\Pages;

use App\Filament\App\Resources\ClubMembers\ClubMemberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClubMembers extends ListRecords
{
    protected static string $resource = ClubMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-user-plus'),
        ];
    }
}

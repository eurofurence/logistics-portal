<?php

namespace App\Filament\App\Resources\ClubMembers\Pages;

use App\Filament\App\Resources\ClubMembers\ClubMemberResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateClubMember extends CreateRecord
{
    protected static string $resource = ClubMemberResource::class;

    protected function getRedirectUrl(): string
    {
        return ClubMemberResource::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->icon('heroicon-o-user-plus');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()->icon('heroicon-o-plus-circle');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->icon('heroicon-o-x-mark');
    }
}

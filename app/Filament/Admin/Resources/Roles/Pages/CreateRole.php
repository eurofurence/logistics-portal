<?php

namespace App\Filament\Admin\Resources\Roles\Pages;

use Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource\Pages\CreateRole as BaseCreateRole;
use App\Filament\Admin\Resources\Roles\RoleResource;

class CreateRole extends BaseCreateRole
{
    protected static string $resource = RoleResource::class;

    protected function beforeValidate(): void
    {
        RoleResource::authorizeMetadata($this->data ?? []);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return RoleResource::filterMetadata($data);
    }
}

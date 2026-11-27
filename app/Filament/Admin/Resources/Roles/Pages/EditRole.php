<?php

namespace App\Filament\Admin\Resources\Roles\Pages;

use Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource\Pages\EditRole as BaseEditRole;
use App\Filament\Admin\Resources\Roles\RoleResource;

class EditRole extends BaseEditRole
{
    protected static string $resource = RoleResource::class;

    protected function beforeValidate(): void
    {
        RoleResource::authorizeMetadata($this->data ?? [], $this->getRecord()->fresh());
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return RoleResource::filterMetadata($data);
    }
}

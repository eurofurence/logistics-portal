<?php

namespace App\Filament\Admin\Resources\Roles\Pages;

use Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource\Pages\ListRoles as BaseListRoles;
use App\Filament\Admin\Resources\Roles\RoleResource;

class ListRoles extends BaseListRoles
{
    protected static string $resource = RoleResource::class;
}

<?php

namespace App\Filament\Admin\Resources\Roles\Pages;

use Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource\Pages\ViewRole as BaseViewRole;
use App\Filament\Admin\Resources\Roles\RoleResource;

class ViewRole extends BaseViewRole
{
    protected static string $resource = RoleResource::class;
}

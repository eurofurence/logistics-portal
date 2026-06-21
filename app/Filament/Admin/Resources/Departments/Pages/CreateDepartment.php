<?php

namespace App\Filament\Admin\Resources\Departments\Pages;

use App\Filament\Admin\Resources\Departments\DepartmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDepartment extends CreateRecord
{
    protected static string $resource = DepartmentResource::class;
}

<?php

namespace App\Filament\Admin\Resources\Departments\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->unique(ignoreRecord: true)
                            ->label(__('general.name'))
                            ->required(),
                        TextInput::make('idp_group_id')
                            ->unique(ignoreRecord: true)
                            ->label(__('general.idp_group'))
                            ->nullable(),
                    ]),
            ]);
    }
}

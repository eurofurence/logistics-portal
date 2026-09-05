<?php

namespace App\Filament\Admin\Resources\IdpRankSyncs\Schemas;

use App\Models\Role;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IdpRankSyncForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->unique(ignoreRecord: true)
                            ->nullable()
                            ->label(__('general.name')),
                        Select::make('local_role')
                            ->options(
                                Role::all(['id', 'name'])->pluck('name', 'id')
                            )
                            ->required()
                            ->exists(table: Role::class, column: 'id')
                            ->label(__('general.local_role')),
                        TextInput::make('idp_group')
                            ->required()
                            ->label(__('general.idp_group')),
                        Toggle::make('active')
                            ->label(__('general.is_active'))
                            ->default(false),
                    ]),
            ]);
    }
}

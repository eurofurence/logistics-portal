<?php

namespace App\Filament\App\Resources\OrderCategories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('general.informations'))
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Textarea::make('description')
                            ->nullable()
                            ->maxLength(1000)
                            ->label(__('general.description')),

                    ]),
            ]);
    }
}

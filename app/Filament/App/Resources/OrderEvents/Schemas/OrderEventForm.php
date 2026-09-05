<?php

namespace App\Filament\App\Resources\OrderEvents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('general.informations'))
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->label(__('general.name'))
                            ->unique(ignoreRecord: true),
                        DateTimePicker::make('order_deadline')
                            ->label(__('general.order_deadline'))
                            ->nullable()
                            ->timezone('Europe/Berlin')
                            ->hint('Europe/Berlin')
                            ->seconds(false),
                    ]),
                Section::make(__('general.options'))
                    ->schema([
                        Section::make([
                            Toggle::make('locked')
                                ->label(__('general.locked'))
                                ->inline()
                                ->default(false),
                        ]),
                        Section::make([
                            Toggle::make('is_active')
                                ->inline()
                                ->default(false)
                                ->helperText(__('general.is_active_description')),
                        ]),
                    ]),
            ]);
    }
}

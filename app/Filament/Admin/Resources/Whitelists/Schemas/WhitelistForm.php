<?php

namespace App\Filament\Admin\Resources\Whitelists\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WhitelistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('email')->required()->unique()->email(),
                    ]),
            ]);
    }
}

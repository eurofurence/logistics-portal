<?php

namespace App\Filament\App\Resources\Wishlists\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class WishlistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->maxLength(255)
                    ->required()
                    ->label(__('general.name')),
                Textarea::make('description')
                    ->maxLength(10000)
                    ->label(__('general.description')),
                Hidden::make('user_id')
                    ->dehydrated(false)
                    ->default(fn () => Auth::id()),
            ]);
    }
}

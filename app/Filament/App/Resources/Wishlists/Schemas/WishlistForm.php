<?php

namespace App\Filament\App\Resources\Wishlists\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Illuminate\Support\Facades\Auth;

class WishlistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->label(__('general.name')),
                Textarea::make('description')
                    ->label(__('general.description')),
                Toggle::make('is_public')
                    ->label(__('general.is_public')),
                Hidden::make('user_id')
                    ->default(fn () => Auth::id()),
            ]);
    }
}

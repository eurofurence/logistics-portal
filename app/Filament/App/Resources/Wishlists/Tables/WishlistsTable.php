<?php

namespace App\Filament\App\Resources\Wishlists\Tables;

use App\Models\Wishlist;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class WishlistsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(Wishlist::query()->where('user_id', Auth::id())
                ->orWhereHas('sharedUsers', fn ($q) => $q->where('user_id', Auth::id())))
            ->columns([
                TextColumn::make('name')
                    ->label(__('general.name')),
                IconColumn::make('is_public')
                    ->boolean()
                    ->label(__('general.is_public')),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

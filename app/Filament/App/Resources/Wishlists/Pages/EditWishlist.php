<?php

namespace App\Filament\App\Resources\Wishlists\Pages;

use App\Filament\App\Resources\Wishlists\WishlistResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWishlist extends EditRecord
{
    protected static string $resource = WishlistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

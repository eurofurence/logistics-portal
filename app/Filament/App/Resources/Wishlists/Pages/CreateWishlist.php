<?php

namespace App\Filament\App\Resources\Wishlists\Pages;

use App\Filament\App\Resources\Wishlists\WishlistResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWishlist extends CreateRecord
{
    protected static string $resource = WishlistResource::class;
}

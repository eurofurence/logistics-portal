<?php

namespace App\Filament\App\Resources\OrderArticles\Pages;

use App\Filament\App\Resources\OrderArticles\OrderArticleResource;
use App\Filament\App\Resources\Wishlists\Concerns\InteractsWithWishlists;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrderArticles extends ListRecords
{
    use InteractsWithWishlists;

    protected static string $resource = OrderArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                $this->wishlistsAction(),
                $this->wishlistTargetAction(),
                $this->newWishlistAction(),
            ])
                ->label(__('wishlists.title'))
                ->icon('heroicon-o-bookmark-square')
                ->color('gray')
                ->button(),
            CreateAction::make()
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}

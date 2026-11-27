<?php

namespace App\Filament\App\Resources\OrderArticles\Pages;

use App\Actions\HeaderOrderAction;
use App\Actions\RefreshOrderArticleImageAction;
use App\Actions\WishlistArticleAction;
use App\Filament\App\Resources\OrderArticles\OrderArticleResource;
use App\Filament\App\Resources\Wishlists\Concerns\InteractsWithWishlists;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;

class ViewOrderArticle extends ViewRecord
{
    use InteractsWithWishlists;

    protected static string $resource = OrderArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                RefreshOrderArticleImageAction::make(),
                EditAction::make()
                    ->color(Color::Amber)
                    ->icon('heroicon-o-pencil'),
            ])
                ->label(__('general.options'))
                ->icon('heroicon-o-ellipsis-vertical')
                ->color('gray')
                ->button(),
            ActionGroup::make([
                $this->wishlistTargetAction(),
                $this->wishlistsAction(),
                $this->newWishlistAction(),
            ])
                ->label(__('wishlists.title'))
                ->icon('heroicon-o-bookmark-square')
                ->color('gray')
                ->button(),
            HeaderOrderAction::make(),
            WishlistArticleAction::make(),
        ];
    }
}

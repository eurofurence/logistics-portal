<?php

namespace App\Actions;

use App\Models\OrderArticle;
use App\Models\Wishlist;
use App\Services\WishlistManager;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class WishlistArticleAction
{
    public static function make(): Action
    {
        return Action::make('add_to_wishlist')
            ->label(function (OrderArticle $record): string {
                return static::isSaved($record) ? __('wishlists.bookmarked') : __('wishlists.bookmark');
            })
            ->icon(function (OrderArticle $record): string {
                return static::isSaved($record) ? 'heroicon-s-bookmark' : 'heroicon-o-bookmark';
            })
            ->iconButton()->tooltip(fn (Action $action): string => $action->getLabel())
            ->color('primary')
            ->authorize(fn (): bool => Gate::allows('viewAny', Wishlist::class) && (Gate::allows('manage-all-Wishlist') || auth()->user()->checkPermissionTo('update-Wishlist')))
            ->action(fn (OrderArticle $record, Component $livewire) => $livewire->toggleWishlistArticle($record->id));
    }

    protected static function isSaved(OrderArticle $record): bool
    {
        if (array_key_exists('is_wishlisted', $record->getAttributes())) {
            return (bool) $record->getAttribute('is_wishlisted');
        }
        $target = Gate::allows('viewAny', Wishlist::class) ? app(WishlistManager::class)->target(auth()->user()) : null;

        return $target && $record->wishlistItems()->where('wishlist_id', $target->id)->exists();
    }
}

@php
    $article = $item->article;
    $visibleArticle = $article && auth()->user()->can('view', $article);
    $unavailable = ! $article || $article->trashed();
    $canEdit = $canEdit ?? auth()->user()->can('manageItems', $wishlist);
@endphp
<article wire:key="wishlist-item-{{ $item->id }}" class="group flex items-start gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-primary-300 dark:border-white/10 dark:bg-gray-900">
    @if (($selectable ?? false) && $canEdit)
        <input type="checkbox" wire:model.live="selectedArticles" value="{{ $item->order_article_id }}" aria-label="{{ __('wishlists.select_article') }}" class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:bg-gray-800" />
    @endif
    @if ($visibleArticle && $article->picture)
        <img src="{{ $article->picture_thumbnail_url }}" alt="" loading="lazy" class="size-20 shrink-0 rounded-xl bg-gray-50 object-contain dark:bg-gray-800" />
    @else
        <div class="flex size-20 shrink-0 items-center justify-center rounded-xl bg-gray-50 text-gray-400 dark:bg-gray-800" aria-label="{{ __('wishlists.no_image') }}">
            <x-filament::icon icon="heroicon-o-cube" class="size-8" />
        </div>
    @endif
    <div class="min-w-0 flex-1 space-y-2">
        @if ($visibleArticle)
            @if ($unavailable)
                <p class="font-semibold text-gray-500 dark:text-gray-400">{{ $article->name }}</p>
            @else
                <a href="{{ \App\Filament\App\Resources\OrderArticles\OrderArticleResource::getUrl('view', ['record' => $article]) }}" class="block font-semibold text-gray-950 hover:text-primary-600 dark:text-white">{{ $article->name }}</a>
            @endif
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $article->article_number }}</p>
            @if (! $unavailable)
                <p class="text-sm font-medium text-gray-950 dark:text-white">{{ number_format($article->price_net, 2, ',', '.') }} {{ $article->currency }}</p>
            @endif
        @else
            <p class="text-sm text-gray-500">{{ __('wishlists.unavailable') }}</p>
        @endif
        @if ($unavailable)
            <x-filament::badge color="danger" icon="heroicon-o-exclamation-triangle">{{ __('wishlists.no_longer_available') }}</x-filament::badge>
        @endif
        @if (($selectable ?? false) && $visibleArticle && ! $unavailable)
            {{ ($this->orderWishlistArticleAction)(['article_id' => $article->id]) }}
        @endif
        @if ($canEdit)
            <button type="button" wire:click="removeWishlistArticle({{ $wishlist->id }}, {{ $item->order_article_id }})" wire:loading.attr="disabled" class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-danger-600 focus-visible:outline-2 focus-visible:outline-primary-500 disabled:opacity-50 dark:text-gray-400">
                <x-filament::icon icon="heroicon-o-x-mark" class="size-4" />{{ __('wishlists.remove') }}
            </button>
        @endif
    </div>
</article>

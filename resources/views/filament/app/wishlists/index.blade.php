<x-filament-panels::page>
    <section class="overflow-hidden rounded-3xl border border-primary-200 bg-linear-to-br from-primary-50 via-white to-gray-50 p-6 dark:border-primary-500/20 dark:from-primary-950 dark:via-gray-900 dark:to-gray-900 sm:p-8">
        <div class="flex items-center gap-4">
            <div class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-primary-600 text-white shadow-sm">
                <x-filament::icon icon="heroicon-o-bookmark-square" class="size-7" />
            </div>
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ __('wishlists.intro') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('wishlists.intro_help') }}</p>
            </div>
        </div>
    </section>

    <div class="grid items-start gap-6 lg:grid-cols-[18rem_minmax(0,1fr)]">
        <aside class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
            <label class="block space-y-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('wishlists.title') }}</span>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="listScope">
                        <option value="mine">{{ __('wishlists.mine') }}</option>
                        <option value="shared">{{ __('wishlists.shared') }}</option>
                        @can('manage-all-Wishlist')
                            <option value="all">{{ __('wishlists.all') }}</option>
                        @endcan
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </label>

            <div class="hidden max-h-[60vh] space-y-2 overflow-y-auto lg:block">
                @forelse ($lists as $list)
                    <button
                        type="button"
                        wire:key="list-{{ $list->id }}"
                        wire:click="chooseWishlist({{ $list->id }})"
                        @class([
                            'w-full rounded-xl border p-3 text-left transition focus-visible:outline-2 focus-visible:outline-primary-500',
                            'border-primary-200 bg-primary-50 dark:border-primary-500/40 dark:bg-primary-500/10' => $wishlist?->id === $list->id,
                            'border-transparent hover:bg-gray-50 dark:hover:bg-white/5' => $wishlist?->id !== $list->id,
                        ])
                    >
                        <span class="flex items-center justify-between gap-2">
                            <span class="truncate font-medium">{{ $list->name }}</span>
                            <span class="text-xs text-gray-500">{{ $list->items_count }}</span>
                        </span>
                        <span class="mt-1 block truncate text-xs text-gray-500">
                            {{ $list->user?->name }} · {{ in_array($list->id, $editableIds, true) ? __('wishlists.editable') : __('wishlists.read_only') }}
                        </span>
                    </button>
                @empty
                    <p class="p-3 text-sm text-gray-500">{{ __('wishlists.empty_lists_help') }}</p>
                @endforelse
            </div>

            <div class="lg:hidden">
                <label class="sr-only" for="mobile-wishlist">{{ __('wishlists.select_list') }}</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select id="mobile-wishlist" wire:model.live="selectedWishlistId">
                        <option value="">{{ __('wishlists.select_list') }}</option>
                        @foreach ($lists as $list)
                            <option value="{{ $list->id }}">{{ $list->name }} ({{ $list->items_count }})</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </aside>

        <section class="min-w-0 space-y-5">
            @if ($wishlist)
                <div class="space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="space-y-1">
                            <h2 class="text-2xl font-semibold tracking-tight">{{ $wishlist->name }}</h2>
                            <p class="text-sm text-gray-500">{{ __('wishlists.owner') }}: {{ $wishlist->user?->name }} · {{ $items->total() }} {{ __('wishlists.items') }}</p>
                            <p class="max-w-2xl text-sm text-gray-500">{{ $wishlist->description }}</p>
                        </div>
                        <x-filament::badge :color="$canEdit ? 'primary' : 'gray'">
                            {{ $canEdit ? __('wishlists.editable') : __('wishlists.read_only') }}
                        </x-filament::badge>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($canEdit)
                            <x-filament::button color="gray" size="sm" icon="heroicon-o-bookmark" wire:click="setQuickTarget">
                                {{ __('wishlists.use_target') }}
                            </x-filament::button>
                        @endif
                        {{ $this->renameWishlistAction }}
                        {{ $this->shareWishlistAction }}
                        {{ $this->transferOwnershipAction }}
                        {{ $this->deleteWishlistAction }}
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="min-w-48 flex-1">
                            <label class="sr-only" for="wishlist-search">{{ __('wishlists.search') }}</label>
                            <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass">
                                <x-filament::input id="wishlist-search" type="search" wire:model.live.debounce.300ms="search" :placeholder="__('wishlists.search')" />
                            </x-filament::input.wrapper>
                        </div>
                        @if ($selectedArticles && $canEdit)
                            <x-filament::button color="danger" size="sm" wire:click="removeSelected" wire:loading.attr="disabled">
                                {{ __('wishlists.remove_selected') }} ({{ count($selectedArticles) }})
                            </x-filament::button>
                        @endif
                    </div>
                </div>

                <div class="grid gap-3 xl:grid-cols-2" wire:loading.class="opacity-60">
                    @forelse ($items as $item)
                        @include('filament.app.wishlists.card', ['selectable' => true])
                    @empty
                        <div class="col-span-full rounded-2xl border border-dashed border-gray-300 p-10 text-center dark:border-white/15">
                            <x-filament::icon icon="heroicon-o-bookmark" class="mx-auto mb-4 size-10 text-gray-300" />
                            <h3 class="font-semibold">{{ __($search !== '' ? 'wishlists.empty_search' : 'wishlists.empty_items') }}</h3>
                            <p class="mt-2 text-sm text-gray-500">{{ __($search !== '' ? 'wishlists.empty_search_help' : 'wishlists.empty_items_help') }}</p>
                            <x-filament::button class="mt-5" tag="a" :href="\App\Filament\App\Resources\OrderArticles\OrderArticleResource::getUrl('index')">
                                {{ __('wishlists.open_directory') }}
                            </x-filament::button>
                        </div>
                    @endforelse
                </div>
                {{ $items->links() }}
            @else
                <div class="rounded-2xl border border-dashed border-gray-300 p-12 text-center dark:border-white/15">
                    <x-filament::icon icon="heroicon-o-sparkles" class="mx-auto mb-4 size-10 text-primary-500" />
                    <h2 class="text-lg font-semibold">{{ __('wishlists.empty_lists') }}</h2>
                    <p class="mt-2 text-sm text-gray-500">{{ __('wishlists.empty_lists_help') }}</p>
                </div>
            @endif
            @error('undo')
                <p role="alert" class="text-sm text-danger-600">{{ $message }}</p>
            @enderror
        </section>
    </div>
</x-filament-panels::page>

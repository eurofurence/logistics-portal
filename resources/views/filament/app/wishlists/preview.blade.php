<div class="space-y-5">
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('wishlists.quick_help') }}</p>
    <label class="block space-y-2">
        <span class="text-sm font-medium">{{ __('wishlists.select_list') }}</span>
        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="previewWishlistId">
                <option value="">{{ __('wishlists.target') }}</option>
                @foreach ($lists as $list)
                    <option value="{{ $list->id }}">{{ $list->name }} ({{ $list->items_count }}) · {{ in_array($list->id, $editableIds, true) ? __('wishlists.editable') : __('wishlists.read_only') }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </label>
    @if ($wishlist)
        @php($canEdit = in_array($wishlist->id, $editableIds, true))
        <div class="flex items-center justify-between gap-3">
            <h3 class="font-semibold">{{ $wishlist->name }}</h3>
            <x-filament::badge color="gray">{{ $wishlist->items()->count() }} {{ __('wishlists.items') }}</x-filament::badge>
        </div>
        <div class="space-y-3">
            @forelse ($wishlist->items()->with('article')->latest('id')->limit(6)->get() as $item)
                @include('filament.app.wishlists.card', ['selectable' => false])
            @empty
                <p class="rounded-xl bg-gray-50 p-6 text-sm text-gray-500 dark:bg-white/5">{{ __('wishlists.empty_items') }}</p>
            @endforelse
        </div>
        <x-filament::button tag="a" :href="\App\Filament\App\Resources\Wishlists\WishlistResource::getUrl('index', ['list' => $wishlist->id])" class="w-full">{{ __('wishlists.open_list') }}</x-filament::button>
    @else
        <p class="rounded-xl bg-gray-50 p-6 text-sm text-gray-500 dark:bg-white/5">{{ __('wishlists.no_target') }}</p>
    @endif
    <x-filament::link :href="\App\Filament\App\Resources\Wishlists\WishlistResource::getUrl('index')">{{ __('wishlists.browse_lists') }}</x-filament::link>
    @error('undo') <p role="alert" class="text-sm text-danger-600">{{ $message }}</p> @enderror
</div>

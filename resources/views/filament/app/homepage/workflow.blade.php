<x-filament::section :heading="__('homepage.workflow')" :description="__('homepage.workflow_intro')">
    <ol class="grid gap-5 sm:grid-cols-2">
        @foreach (['wishlists', 'orders', 'bills', 'inventory'] as $area)
            @if (isset($sections[$area]))
                <li class="flex items-start gap-3">
                    <x-filament::icon :icon="$sections[$area]['icon']" class="mt-1 size-5 shrink-0 text-primary-500" />
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $sections[$area]['title'] }}</p>
                        <p class="text-sm leading-6 text-gray-600 dark:text-gray-400">{{ __('homepage.'.$area.'_workflow') }}</p>
                    </div>
                </li>
            @endif
        @endforeach
    </ol>
</x-filament::section>

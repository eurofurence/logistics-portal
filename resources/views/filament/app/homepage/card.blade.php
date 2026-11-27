<div class="space-y-5">
    @if ($isGuide || $area === 'inventory')
        <p class="whitespace-pre-line text-sm leading-6 text-gray-600 dark:text-gray-400">{{ $section['description'] }}</p>
    @else
        <div class="flex items-baseline gap-3">
            <span class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ number_format($section['count']) }}</span>
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('homepage.'.$area.'_count') }}</span>
        </div>
        @if ($section['records'] !== [])
            <ul class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach ($section['records'] as $record)
                    <li>
                        <a href="{{ $record['url'] }}" class="group flex items-center justify-between gap-3 rounded-lg py-3 focus-visible:outline-2 focus-visible:outline-primary-500">
                            <span class="min-w-0 space-y-1">
                                <span class="block truncate text-sm font-medium text-gray-950 group-hover:text-primary-600 dark:text-white dark:group-hover:text-primary-400">{{ $record['title'] }}</span>
                                @if ($record['department'])
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $record['department'] }}</span>
                                @endif
                            </span>
                            @if ($record['status'])
                                <x-filament::badge color="gray">{{ $record['status'] }}</x-filament::badge>
                            @else
                                <x-filament::icon icon="heroicon-o-chevron-right" class="size-4 shrink-0 text-gray-400" />
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="rounded-xl bg-gray-50 p-4 text-sm text-gray-500 dark:bg-white/5 dark:text-gray-400">{{ __('homepage.'.$area.'_empty') }}</p>
        @endif
    @endif
    <div class="flex flex-wrap gap-3 border-t border-gray-100 pt-4 dark:border-white/10">
        @foreach ($section['links'] as $link)
            <x-filament::button tag="a" :href="$link['url']" color="gray" size="sm" outlined>{{ $link['label'] }}</x-filament::button>
        @endforeach
    </div>
</div>

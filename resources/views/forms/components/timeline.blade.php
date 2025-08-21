<div class="space-y-4">
    @if (!empty($getRecord()))
        @if ($getRecord()->statusHistory()->count() > 0)
            @foreach ($getRecord()->statusHistory() as $entry)
                <div class="flex items-start gap-4 group">
                    <div class="relative mt-1">
                        <div
                            class="relative z-10 flex items-center justify-center flex-shrink-0 w-8 h-8 rounded-full shadow-md ring-1 ring-gray-300">
                            <x-dynamic-component :component="$entry->icon"
                                class="w-5 h-5" />
                        </div>
                    </div>
                    <div
                        class="flex-1 p-4 border rounded-lg shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="font-medium text-l">
                                {{ __($entry->title) }}
                            </h3>
                            <time class="text-xs">
                                {{ $entry->created_at->format('d.m.Y H:i') }}
                            </time>
                        </div>
                        @if ($entry->description)
                            <p class="mt-2 text-sm font-thin">
                                {{ __($entry->description['key'], $entry->description['params'] ?? []) }}
                            </p>
                        @endif
                        @if ($entry->user)
                            <p class="mt-3 text-xs font-medium text-gray-500 dark:text-gray-400">
                                {{ __('timeline.edited_by', ['user' => $entry->user->name]) }}
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <!-- No entries -->
            <div class="flex items-start gap-4">
                <div class="relative mt-1">
                    <div
                        class="relative z-10 flex items-center justify-center flex-shrink-0 w-8 h-8 bg-gray-100 rounded-full ring-1 ring-gray-300 dark:bg-gray-700 dark:ring-gray-600">
                        <x-dynamic-component component="heroicon-o-information-circle"
                            class="w-5 h-5 text-gray-400 dark:text-gray-400" />
                    </div>
                </div>
                <div
                    class="flex-1 p-4 border rounded-lg">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-300">
                        {{ __('timeline.no_entries') }}
                    </h3>
                </div>
            </div>
        @endif
    @else
        <div class="flex items-start gap-4">
            <div class="relative mt-1">
                <div
                    class="relative z-10 flex items-center justify-center flex-shrink-0 w-8 h-8 bg-gray-100 rounded-full ring-1 ring-gray-300 dark:bg-gray-700 dark:ring-gray-600">
                    <x-dynamic-component component="heroicon-o-information-circle"
                        class="w-5 h-5 text-gray-400 dark:text-gray-400" />
                </div>
            </div>
            <div
                class="flex-1 p-4 border rounded-lg">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-300">
                    {{ __('timeline.please_create_model_first') }}
                </h3>
            </div>
        </div>
    @endif
</div>

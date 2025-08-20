<div class="space-y-4">
    @if (!empty($getRecord()))
        @if ($getRecord()->statusHistory()->count() > 0)
            @foreach ($getRecord()->statusHistory() as $entry)
                <div class="flex group items-start gap-4 transition-all duration-200 hover:translate-x-1">
                    <div class="relative mt-1">
                        <div
                            class="relative z-10 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-50 shadow-md ring-1 ring-blue-200 transition-all duration-200 hover:scale-110 hover:ring-2 hover:ring-blue-500 dark:bg-blue-900/80 dark:ring-blue-800 dark:hover:ring-blue-400">
                            <x-dynamic-component :component="$entry->icon" class="h-5 w-5 text-blue-600 dark:text-blue-300" />
                        </div>

                    </div>

                    <div
                        class="flex-1 rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-all duration-200 hover:border-blue-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900 dark:hover:border-blue-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ __($entry->title) }}
                            </h3>
                            <time class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $entry->created_at->format('d.m.Y H:i') }}
                            </time>
                        </div>
                        @if ($entry->description)
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
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
                        class="relative z-10 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gray-100 ring-1 ring-gray-300 dark:bg-gray-800 dark:ring-gray-700">
                        <x-dynamic-component component="heroicon-o-information-circle"
                            class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                    </div>
                </div>
                <div
                    class="flex-1 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900/50">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ __('timeline.no_entries') }}
                    </h3>
                </div>
            </div>
        @endif
    @else
        <div class="flex items-start gap-4">
            <div class="relative mt-1">
                <div
                    class="relative z-10 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gray-100 ring-1 ring-gray-300 dark:bg-gray-800 dark:ring-gray-700">
                    <x-dynamic-component component="heroicon-o-information-circle"
                        class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                </div>
            </div>
            <div
                class="flex-1 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900/50">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ __('timeline.please_create_model_first') }}
                </h3>
            </div>
        </div>
    @endif
</div>

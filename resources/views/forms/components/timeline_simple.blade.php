<div class="space-y-4">
    @if (!empty($getRecord()))
        @if ($getRecord()->statusHistory()->count() > 0)
            @foreach ($getRecord()->statusHistory() as $entry)
                <div class="flex gap-3">
                    <!-- Icon -->
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100">
                        <x-dynamic-component :component="$entry->icon" class="h-5 w-5 text-gray-600" />
                    </div>

                    <!-- Contents -->
                    <div class="flex-1 space-y-1">
                        <div class="flex items-center gap-2">
                            <h3 class="font-medium text-gray-900 dark:text-white">
                                {{ __($entry->title) }}
                            </h3>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $entry->created_at->diffForHumans() }}
                            </span>
                        </div>
                        @if ($entry->description)
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                {{ __($entry->description['key'], $entry->description['params'] ?? []) }}
                            </p>
                        @endif
                        @if ($entry->user)
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('timeline.edited_by', ['user' => $entry->user->name]) }}
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <!-- No entries -->
            <div class="flex gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100">
                    <x-dynamic-component component="heroicon-o-information-circle"
                        class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                </div>
                <div class="flex-1 space-y-1">
                    <h3 class="font-medium text-gray-400 dark:text-gray-500">
                        {{ __('timeline.no_entries') }}
                    </h3>
                </div>
            </div>
        @endif
    @else
        <div class="flex gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100">
                <x-dynamic-component component="heroicon-o-information-circle"
                    class="h-5 w-5 text-gray-400 dark:text-gray-500" />
            </div>
            <div class="flex-1 space-y-1">
                <h3 class="font-medium text-gray-400 dark:text-gray-500">
                    {{ __('timeline.please_create_model_first') }}
                </h3>
            </div>
        </div>
    @endif
</div>

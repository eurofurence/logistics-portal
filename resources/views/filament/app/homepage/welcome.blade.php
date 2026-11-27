<div class="relative overflow-hidden rounded-2xl bg-primary-50 p-6 ring-1 ring-primary-200 dark:bg-primary-950 dark:ring-primary-800 sm:p-8">
    <div class="flex items-start gap-4">
        <div class="hidden rounded-2xl bg-primary-100 p-3 text-primary-600 dark:bg-primary-900 dark:text-primary-300 sm:block">
            <x-filament::icon icon="heroicon-o-home" class="size-8" />
        </div>
        <div class="max-w-3xl space-y-3">
            <p class="text-sm font-medium text-primary-700 dark:text-primary-300">{{ __('homepage.personal_workspace') }}</p>
            <h2 class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white sm:text-3xl">{{ $title }}, {{ $name }}</h2>
            <p class="whitespace-pre-line text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $intro }}</p>
            @unless ($hasContent)
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('homepage.no_sections') }}</p>
            @endunless
        </div>
    </div>
</div>

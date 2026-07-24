<x-filament-widgets::widget>
    @php
        $count = $this->getOpenBillsCount();
        $events = $this->getEventsWithOpenBills();
    @endphp

    @if ($count > 0)
        <div
            wire:key="other-events-open-bills-alert-{{ $count }}"
            class="fi-wi-other-events-open-bills-alert bg-danger-600 rounded-xl p-4 shadow-sm ring-1 ring-danger-900/50 border border-danger-700"
        >
            <div class="flex items-center gap-x-3">
                <div class="flex-shrink-0" style="margin-right: 15px">
                    <x-filament::icon
                        icon="heroicon-m-exclamation-triangle"
                        class="h-6 w-6 text-white"
                    />
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold text-white leading-6">
                        <b>{{ __('general.open_bills_in_other_events') }}</b>
                    </p>
                    <p class="text-sm text-white/90">
                        {{ __('general.open_bills_in_other_events_hint', ['count' => $count]) }}
                    </p>
                    @if ($events->isNotEmpty())
                        <div class="mt-2 text-sm text-white/80">
                            <ul class="list-disc list-inside">
                                @foreach ($events as $eventName)
                                    <li>- {{ $eventName }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</x-filament-widgets::widget>

<!-- Include the ZXing Library and your custom barcode scanner script -->
@vite('resources/js/components/barcode-scanner.js')
@php
    $isDisabled = $isDisabled();
    $icon = $getExtraAttributes()['icon'] ?? null;
    $title = $getExtraAttributes()['title'] ?? null;
@endphp
<div xmlns:x-filament="http://www.w3.org/1999/html">
    <x-dynamic-component
        :component="$getFieldWrapperView()"
        :field="$field"
    >
        <x-filament::input.wrapper :disabled="$isDisabled">
            <div class="flex w-full items-center">
                <x-filament::input
                    type="text"
                    name="{{ $getName() }}"
                    id="{{ $getId() }}"
                    value="{{ $getState() }}"
                    placeholder="{{ $getPlaceholder() }}"
                    class="min-w-0 flex-1"
                    wire:model="{{ $getStatePath() }}"
                    :disabled="$isDisabled"
                />
                <!-- Trigger Button for Filament Modal -->
                @if (! $isDisabled)
                    <button
                        type="button"
                        onclick="openScannerModal('{{ $getId() }}')"
                        class="flex shrink-0 items-center px-3 focus:outline-none"
                        aria-label="@lang('general.scan_code')"
                    >
                        @if ($icon)
                            <x-filament::icon
                                :icon="$icon"
                                class="h-5 w-5 text-gray-400 dark:text-gray-200"
                            />
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 dark:text-gray-200"
                                viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M3 4h2v16H3V4zm4 0h2v16H7V4zm4 0h2v16h-2V4zm4 0h2v16h-2V4zm4 0h2v16h-2V4z" />
                            </svg>
                        @endif
                    </button>
                @endif
            </div>
        </x-filament::input.wrapper>
    </x-dynamic-component>
    <!-- Filament Modal for Barcode Scanner -->
    <x-filament::modal id="barcode-scanner-modal">
        <x-slot name="header">
            <h2 class="text-lg font-semibold">
                @if ($title)
                    {{ $title }}
                @else
                    @lang('general.scan_code')
                @endif
            </h2>
        </x-slot>
        <div class="p-4">
            <div id="scanner-container">
                <video id="scanner" autoplay class="rounded-lg shadow" style="display: none;"></video>
                <div class="overlay">
                    <div class="scan-area"></div>
                </div>
            </div>
        </div>
        <div class="mt-40">
            <label for="cameraSelect">@lang('general.select_camera')</label>
            <x-filament::input.wrapper>
                <x-filament::input.select class="form-control" id="cameraSelect" onchange="changeCamera()">
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
        <div>
            <label for="barcodeImageUpload"
                class="block text-sm font-medium leading-6 text-gray-700 dark:text-gray-200">
                @lang('general.upload_image')
            </label>
            <x-filament::input.wrapper>
                <x-filament::input type="file" id="barcodeImageUpload" accept="image/*" onchange="scanFromImage(this.files[0])"/>
            </x-filament::input.wrapper>
        </div>
        <x-slot name="footer">
            <x-filament::button onclick="closeScannerModal()" color="danger">
                @lang('general.close')
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</div>

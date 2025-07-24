<!-- Include the ZXing Library and your custom barcode scanner script -->
@vite('resources/js/components/barcode-scanner.js')
<div xmlns:x-filament="http://www.w3.org/1999/html">
    <div class="grid gap-y-2">
        <div class="flex items-center justify-between gap-x-3">
            <label for="{{ $getId() }}" class="inline-flex items-center fi-fo-field-wrp-label gap-x-3">
                <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                    {{ $getLabel() ?? 'Input Label' }}
                    @if ($isRequired())
                        <sup class="font-medium text-danger-600 dark:text-danger-400">*</sup>
                    @endif
                </span>
            </label>
        </div>
        <x-filament::input.wrapper class="relative" :disabled="$isDisabled()">
            <x-filament::input
                type="text"
                name="{{ $getName() }}"
                id="{{ $getId() }}"
                value="{{ $getState() }}"
                placeholder="{{ $getPlaceholder() }}"
                class="w-full pr-10"
                wire:model="{{ $getId() }}"
                :disabled="$isDisabled()"
            />
            <!-- Trigger Button for Filament Modal -->
            <button
                type="button"
                onclick="openScannerModal('{{ $getId() }}')"
                class="absolute inset-y-0 right-0 flex items-center pr-3 mr-3 focus:outline-none"
                aria-label="@lang('general.scan_code')"
                @if ($isDisabled())
                    disabled
                @endif
            >
                @if ($getExtraAttributes()['icon'] ?? null)
                    <span class="text-gray-400 dark:text-gray-200">
                        <x-dynamic-component :component="$getExtraAttributes()['icon']" class="w-5 h-5" />
                    </span>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400 dark:text-gray-200"
                        viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M3 4h2v16H3V4zm4 0h2v16H7V4zm4 0h2v16h-2V4zm4 0h2v16h-2V4zm4 0h2v16h-2V4z" />
                    </svg>
                @endif
            </button>
        </x-filament::input.wrapper>
    </div>
    <!-- Filament Modal for Barcode Scanner -->
    <x-filament::modal id="barcode-scanner-modal">
        <x-slot name="header">
            <h2 class="text-lg font-semibold">
                @if ($getExtraAttributes()['title'] ?? null)
                    {{ $getExtraAttributes()['title'] }}
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

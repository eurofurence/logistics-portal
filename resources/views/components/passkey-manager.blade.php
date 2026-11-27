<div class="fi-sc fi-sc-has-gap">
    <form wire:submit="validatePasskeyProperties" class="fi-sc fi-sc-has-gap">
        <div class="fi-fo-field">
            <label for="passkey-name" class="fi-fo-field-label-content">{{ __('filament-passkeys::passkeys.name') }}</label>
            <x-filament::input.wrapper :valid="! $errors->has('name')">
                <x-filament::input id="passkey-name" type="text" wire:model="name" :placeholder="__('settings.passkey_placeholder')" autocomplete="off" />
            </x-filament::input.wrapper>
            @error('name')
                <p class="fi-fo-field-wrp-error-message" role="alert">{{ $message }}</p>
            @enderror
        </div>
        <div class="mt-4 mb-4">
            <x-filament::button type="submit" icon="heroicon-o-plus" wire:loading.attr="disabled" wire:target="validatePasskeyProperties,storePasskey">
                {{ __('settings.add_passkey') }}
            </x-filament::button>
        </div>
    </form>

    @forelse ($passkeys as $passkey)
        <x-filament::section :heading="$passkey->name" icon="heroicon-o-key" compact secondary wire:key="passkey-{{ $passkey->id }}">
            <x-slot name="description">
                {{ __('passkeys::passkeys.last_used') }}: {{ $passkey->last_used_at?->diffForHumans() ?? __('passkeys::passkeys.not_used_yet') }}
            </x-slot>
            <x-slot name="afterHeader">{{ ($this->deleteAction)(['passkey' => $passkey->id]) }}</x-slot>
        </x-filament::section>
    @empty
        <x-filament::section icon="heroicon-o-device-phone-mobile" compact secondary>
            <x-slot name="heading">{{ __('settings.empty_passkeys') }}</x-slot>
            <x-slot name="description">{{ __('settings.empty_passkeys_description') }}</x-slot>
        </x-filament::section>
    @endforelse

    <x-filament-actions::modals />
</div>

@include('passkeys::livewire.partials.createScript')

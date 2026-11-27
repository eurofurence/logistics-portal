@if (auth()->check() && filled(config('webpush.vapid.public_key')) && filled(config('webpush.vapid.private_key')))
    <div
        data-push-controls
        x-data="portalPushNotifications"
        x-show="supported"
        x-on:open-push-settings.window="openSettings()"
        x-on:modal-closed.window="if ($event.detail.id === 'portal-push-settings') dismissPrompt()"
        x-cloak
        data-public-key="{{ config('webpush.vapid.public_key') }}"
        data-store-url="{{ route('push-subscriptions.store', absolute: false) }}"
        data-delete-url="{{ route('push-subscriptions.destroy', absolute: false) }}"
        data-enable-label="{{ __('pwa.push_enable') }}"
        data-disable-label="{{ __('pwa.push_disable') }}"
        data-denied-label="{{ __('pwa.push_denied') }}"
        data-error-label="{{ __('pwa.push_error') }}"
        data-active-label="{{ __('pwa.push_active') }}"
        data-inactive-label="{{ __('pwa.push_inactive') }}"
        data-busy-label="{{ __('pwa.push_busy') }}"
        data-retry-label="{{ __('pwa.retry') }}"
        data-worker-error-label="{{ __('pwa.push_worker_error') }}"
    >
        <x-filament::modal id="portal-push-settings" width="md" icon="heroicon-o-bell-alert" alignment="center" footer-actions-alignment="center" teleport="body">
            <x-slot name="heading">{{ __('pwa.push_title') }}</x-slot>
            <x-slot name="description">{{ __('pwa.push_description') }}</x-slot>

            <x-filament::callout color="gray" icon="heroicon-o-device-phone-mobile" :heading="__('pwa.push_device')">
                <x-slot name="description">
                    <span data-push-status role="status" aria-live="polite" x-text="status">{{ __('pwa.push_inactive') }}</span>
                </x-slot>
            </x-filament::callout>

            <x-slot name="footer">
                <div class="fi-modal-footer-actions">
                    <x-filament::button type="button" color="gray" outlined x-on:click="$dispatch('close-modal', { id: 'portal-push-settings' })">
                        {{ __('pwa.push_close') }}
                    </x-filament::button>
                    <x-filament::button type="button" icon="heroicon-o-bell-alert" data-push-toggle x-on:click="toggle()" x-bind:disabled="actionDisabled" x-bind:aria-busy="pending">
                        <span data-push-toggle-label x-text="buttonLabel">{{ __('pwa.push_enable') }}</span>
                    </x-filament::button>
                </div>
            </x-slot>
        </x-filament::modal>
    </div>
@endif

@if (app(\App\Settings\LoginSettings::class)->passkeys_enabled && ! session()->has('passkeys.pending'))
    <div x-data="{
        busy: false,
        error: '',
        async authenticate() {
            this.busy = true;
            this.error = '';
            try {
                const response = await fetch(@js(route('passkeys.authentication_options', ['panel' => filament()->getCurrentOrDefaultPanel()->getId()])));
                if (!response.ok) { throw new Error(); }
                const credential = await startAuthentication({ optionsJSON: await response.json() });
                this.$refs.credential.value = JSON.stringify(credential);
                this.$refs.login.submit();
            } catch (error) {
                if (error.name !== 'NotAllowedError') {
                    this.error = @js(__('passkeys::passkeys.invalid'));
                }
                this.busy = false;
            }
        }
    }">
        <form x-ref="login" method="POST" action="{{ route('passkeys.login') }}">
            @csrf
            <input x-ref="credential" type="hidden" name="start_authentication_response">
        </form>
        <x-filament::button type="button" icon="heroicon-o-key" color="gray" class="w-full" x-on:click="authenticate()" x-bind:disabled="busy">
            {{ __('passkeys::passkeys.authenticate_using_passkey') }}
        </x-filament::button>
        <p x-show="error" x-text="error" x-cloak role="alert" class="fi-fo-field-wrp-error-message"></p>
        @if ($message = session('authenticatePasskey::message'))
            <p role="alert" class="fi-fo-field-wrp-error-message">{{ $message }}</p>
        @endif
    </div>
@endif

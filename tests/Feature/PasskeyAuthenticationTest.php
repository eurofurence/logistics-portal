<?php

use App\Filament\Admin\Pages\ManageLogin;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\ManagePasskeys;
use App\Models\Permission;
use App\Models\User;
use App\Settings\LoginSettings;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;
use Spatie\LaravelPasskeys\Models\Passkey;

test('blocks passkey endpoints with 403 when globally disabled', function () {
    $this->get(route('passkeys.authentication_options', ['panel' => 'app']))->assertForbidden();
    $this->post(route('passkeys.login'), ['start_authentication_response' => '{}'])->assertForbidden();
    $this->assertGuest();
});

test('allows administrators to enable passkeys independently of two factor authentication', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'), Permission::findOrCreate('access-login-settings', 'web'));
    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(ManageLogin::class)->fillForm(['passkeys_enabled' => true, 'two_factor_enabled' => false])
        ->call('save')->assertHasNoFormErrors();

    expect(app(LoginSettings::class)->refresh()->passkeys_enabled)->toBeTrue();
    expect(app(LoginSettings::class)->refresh()->two_factor_enabled)->toBeFalse();
});

test('generates authentication options requiring user verification for each panel', function (string $panel) {
    $settings = app(LoginSettings::class);
    $settings->passkeys_enabled = true;
    $settings->save();

    $this->get(route('passkeys.authentication_options', ['panel' => $panel]))
        ->assertOk()->assertJsonPath('userVerification', 'required');

    expect(session('passkeys.panel'))->toBe($panel);
    expect(session('passkey-authentication-options'))->toBeString();
})->with(['app', 'admin']);

test('rejects invalid assertions and consumes their challenge', function () {
    $settings = app(LoginSettings::class);
    $settings->passkeys_enabled = true;
    $settings->save();
    $this->get(route('passkeys.authentication_options', ['panel' => 'app']))->assertOk();

    $this->from(route('filament.app.auth.login'))->post(route('passkeys.login'), ['start_authentication_response' => '{}'])
        ->assertRedirect(route('filament.app.auth.login'));

    $this->assertGuest();
    expect(session('passkey-authentication-options'))->toBeNull();
});

test('logs in with a verified passkey when two factor authentication is disabled', function (string $panel) {
    $settings = app(LoginSettings::class);
    $settings->passkeys_enabled = true;
    $settings->whitelist_active = false;
    $settings->save();
    $user = User::factory()->create(['two_factor_required' => true]);
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $passkey = Passkey::factory()->for($user, 'authenticatable')->create();
    $this->mock(FindPasskeyToAuthenticateAction::class)->shouldReceive('execute')->once()->andReturn($passkey);
    $this->get(route('passkeys.authentication_options', ['panel' => $panel]))->assertOk();

    $this->post(route('passkeys.login'), ['start_authentication_response' => '{}'])
        ->assertRedirect(Filament::getPanel($panel)->getUrl());

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login)->not->toBeNull();
})->with(['app', 'admin']);

test('rejects verified passkeys for locked users or users without panel or whitelist access', function (string $panel, bool $locked, bool $panelAccess, bool $whitelist) {
    $settings = app(LoginSettings::class);
    $settings->passkeys_enabled = true;
    $settings->whitelist_active = $whitelist;
    $settings->save();
    $user = User::factory()->create(['locked' => $locked]);
    if ($panelAccess) {
        $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    }
    $passkey = Passkey::factory()->for($user, 'authenticatable')->create();
    $this->mock(FindPasskeyToAuthenticateAction::class)->shouldReceive('execute')->once()->andReturn($passkey);
    $this->get(route('passkeys.authentication_options', ['panel' => $panel]))->assertOk();

    $this->from(route('filament.'.$panel.'.auth.login'))->post(route('passkeys.login'), ['start_authentication_response' => '{}'])
        ->assertRedirect(route('filament.'.$panel.'.auth.login'));

    $this->assertGuest();
})->with([
    'locked app account' => ['app', true, true, false],
    'locked admin account' => ['admin', true, true, false],
    'no admin access' => ['admin', false, false, false],
    'not whitelisted' => ['app', false, true, true],
]);

test('keeps verified passkey users unauthenticated until they pass two factor authentication', function (string $panel) {
    $settings = app(LoginSettings::class);
    $settings->passkeys_enabled = true;
    $settings->two_factor_enabled = true;
    $settings->whitelist_active = false;
    $settings->save();
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
    $user->saveAppAuthenticationRecoveryCodes([Hash::make('recovery-login-code')]);
    $passkey = Passkey::factory()->for($user, 'authenticatable')->create();
    $this->mock(FindPasskeyToAuthenticateAction::class)->shouldReceive('execute')->once()->andReturn($passkey);
    $this->get(route('passkeys.authentication_options', ['panel' => $panel]))->assertOk();

    $this->post(route('passkeys.login'), ['start_authentication_response' => '{}'])
        ->assertRedirect(route('filament.'.$panel.'.auth.login'));
    $this->assertGuest();
    Filament::setCurrentPanel(Filament::getPanel($panel));
    $component = Livewire::test(Login::class)
        ->set('data.multiFactor.app.code', 'invalid')
        ->call('authenticate')->assertHasErrors(['data.multiFactor.app.code']);
    $this->assertGuest();

    $component->set('data.multiFactor.app.code', null)
        ->set('data.multiFactor.app.useRecoveryCode', true)
        ->set('data.multiFactor.app.recoveryCode', 'recovery-login-code')
        ->call('authenticate')->assertHasNoFormErrors()->assertRedirect();

    $this->assertAuthenticatedAs($user);
    expect(session('passkeys.pending'))->toBeNull();
    expect($user->fresh()->getAppAuthenticationRecoveryCodes())->toBe([]);
})->with(['app', 'admin']);

test('shows passkeys in the security tab independently of two factor authentication', function (string $panel) {
    $settings = app(LoginSettings::class);
    $settings->passkeys_enabled = true;
    $settings->whitelist_active = false;
    $settings->save();
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel($panel));

    Livewire::test(EditProfile::class)->assertSee('Security')->assertSee(__('settings.empty_passkeys'))
        ->assertDontSee(__('settings.two_factor_title'));
})->with(['app', 'admin']);

test('prevents deleting another users passkey', function () {
    $settings = app(LoginSettings::class);
    $settings->passkeys_enabled = true;
    $settings->whitelist_active = false;
    $settings->save();
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $passkey = Passkey::factory()->for($otherUser, 'authenticatable')->create();
    $ownPasskey = Passkey::factory()->for($user, 'authenticatable')->create();
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(ManagePasskeys::class)->call('deletePasskey', $passkey->id)->assertOk();
    $this->assertDatabaseHas('passkeys', ['id' => $passkey->id]);

    Livewire::test(ManagePasskeys::class)->call('deletePasskey', $ownPasskey->id)->assertOk();
    $this->assertDatabaseMissing('passkeys', ['id' => $ownPasskey->id]);
});

test('blocks passkey management after the global feature is switched off', function () {
    $settings = app(LoginSettings::class);
    $settings->passkeys_enabled = true;
    $settings->whitelist_active = false;
    $settings->save();
    $user = User::factory()->create();
    $passkey = Passkey::factory()->for($user, 'authenticatable')->create();
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    $component = Livewire::test(ManagePasskeys::class);
    $settings->passkeys_enabled = false;
    $settings->save();

    $component->call('deletePasskey', $passkey->id)->assertForbidden();
    $this->assertDatabaseHas('passkeys', ['id' => $passkey->id]);
});

test('generates discoverable registration options with required device verification', function () {
    $settings = app(LoginSettings::class);
    $settings->passkeys_enabled = true;
    $settings->whitelist_active = false;
    $settings->save();
    $user = User::factory()->create();
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(ManagePasskeys::class)->set('name', 'Laptop')
        ->call('validatePasskeyProperties')->assertHasNoErrors()->assertDispatched('passkeyPropertiesValidated');

    $options = json_decode(session('passkey-registration-options'), true);
    expect($options['authenticatorSelection']['userVerification'])->toBe('required');
    expect($options['authenticatorSelection']['residentKey'])->toBe('required');
});

test('requires enrollment for mandatory users who sign in with a passkey', function (string $panel) {
    $settings = app(LoginSettings::class);
    $settings->passkeys_enabled = true;
    $settings->two_factor_enabled = true;
    $settings->whitelist_active = false;
    $settings->save();
    $user = User::factory()->create(['two_factor_required' => true]);
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $passkey = Passkey::factory()->for($user, 'authenticatable')->create();
    $this->mock(FindPasskeyToAuthenticateAction::class)->shouldReceive('execute')->once()->andReturn($passkey);
    $this->get(route('passkeys.authentication_options', ['panel' => $panel]))->assertOk();

    $this->post(route('passkeys.login'), ['start_authentication_response' => '{}'])->assertRedirect();
    $this->get(route('filament.'.$panel.'.auth.profile'))
        ->assertRedirect(route('filament.'.$panel.'.auth.multi-factor-authentication.set-up-required'));
})->with(['app', 'admin']);

test('rejects expired pending passkey authentication before accepting a recovery code', function () {
    $settings = app(LoginSettings::class);
    $settings->passkeys_enabled = true;
    $settings->two_factor_enabled = true;
    $settings->whitelist_active = false;
    $settings->save();
    $user = User::factory()->create();
    $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
    $user->saveAppAuthenticationRecoveryCodes([Hash::make('recovery-login-code')]);
    $passkey = Passkey::factory()->for($user, 'authenticatable')->create();
    $this->withSession(['passkeys.pending' => [
        'user_id' => $user->id,
        'passkey_id' => $passkey->id,
        'panel' => 'app',
        'expires_at' => now()->addMinutes(5)->timestamp,
        'remember' => false,
    ]]);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    $component = Livewire::test(Login::class);
    $pending = session('passkeys.pending');
    $pending['expires_at'] = now()->subMinute()->timestamp;
    session()->put('passkeys.pending', $pending);

    $component->call('authenticate')->assertHasErrors(['data.email']);

    $this->assertGuest();
    expect(session('passkeys.pending'))->toBeNull();
    expect($user->fresh()->getAppAuthenticationRecoveryCodes())->toHaveCount(1);
});

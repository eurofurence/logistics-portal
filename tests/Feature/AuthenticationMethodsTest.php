<?php

use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use App\Models\Permission;
use App\Models\User;
use App\Settings\LoginSettings;
use Filament\Facades\Filament;
use Livewire\Livewire;

test('two factor authentication is disabled by default', function () {
    expect(app(LoginSettings::class)->two_factor_enabled)->toBeFalse();
    expect(app(LoginSettings::class)->passkeys_enabled)->toBeFalse();
});

test('requires a second factor only when globally enabled', function (string $panel, bool $enabled) {
    Filament::setCurrentPanel(Filament::getPanel($panel));
    $settings = app(LoginSettings::class);
    $settings->two_factor_enabled = $enabled;
    $settings->save();
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

    $component = Livewire::test(Login::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertHasNoFormErrors();

    if ($enabled) {
        $this->assertGuest();
        expect($component->get('userUndertakingMultiFactorAuthentication'))->not->toBeNull();
    } else {
        $component->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }
})->with(['app', 'admin'])->with([true, false]);

test('rejects an invalid second factor before authenticating', function (string $panel) {
    Filament::setCurrentPanel(Filament::getPanel($panel));
    $settings = app(LoginSettings::class);
    $settings->two_factor_enabled = true;
    $settings->save();
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

    Livewire::test(Login::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'password')
        ->call('authenticate')
        ->set('data.multiFactor.app.code', 'invalid')
        ->call('authenticate')
        ->assertHasErrors(['data.multiFactor.app.code']);

    $this->assertGuest();
})->with(['app', 'admin']);

test('requires setup only for marked users while globally enabled', function (string $panel, bool $enabled, bool $required, bool $redirects) {
    $settings = app(LoginSettings::class);
    $settings->two_factor_enabled = $enabled;
    $settings->whitelist_active = false;
    $settings->save();
    $user = User::factory()->create(['two_factor_required' => $required]);
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $this->actingAs($user);

    $response = $this->get(route('filament.'.$panel.'.auth.profile'));

    if ($redirects) {
        $response->assertRedirect(route('filament.'.$panel.'.auth.multi-factor-authentication.set-up-required'));
        $this->get(route('filament.'.$panel.'.auth.multi-factor-authentication.set-up-required'))->assertOk();
    } else {
        $response->assertOk();
    }
})->with(['app', 'admin'])->with([
    'mandatory and globally enabled' => [true, true, true],
    'optional and globally enabled' => [true, false, false],
    'mandatory but globally disabled' => [false, true, false],
    'optional and globally disabled' => [false, false, false],
]);

test('saves the two factor requirement from the admin user form', function (bool $required) {
    $admin = User::factory()->create();
    foreach (['access-adminpanel', 'access-user-navigation', 'view-any-User', 'view-User', 'update-User'] as $permission) {
        $admin->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $user = User::factory()->create(['two_factor_required' => ! $required]);

    Livewire::test(EditUser::class, ['record' => $user->id])
        ->fillForm(['two_factor_required' => $required])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->fresh()->two_factor_required)->toBe($required);
})->with([true, false]);

test('allows disabling a second factor only for optional users', function (string $panel, bool $required) {
    $settings = app(LoginSettings::class);
    $settings->two_factor_enabled = true;
    $settings->save();
    $user = User::factory()->create(['two_factor_required' => $required]);
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel($panel));

    $component = Livewire::test(EditProfile::class);
    if ($required) {
        $component->assertDontSee(__('filament-panels::auth/multi-factor/app/actions/disable.label'));
    } else {
        $component->assertSee(__('filament-panels::auth/multi-factor/app/actions/disable.label'));
    }

    $disableAction = collect(Filament::getMultiFactorAuthenticationProviders()['app']->getActions())
        ->first(fn ($action): bool => $action->getName() === 'disableAppAuthentication');
    expect($disableAction->isAuthorized())->toBe(! $required);

    expect($user->fresh()->getAppAuthenticationSecret())->toBe('JBSWY3DPEHPK3PXP');
})->with(['app', 'admin'])->with([true, false]);

test('allows mandatory users with an enrolled authenticator to access their profile', function (string $panel) {
    $settings = app(LoginSettings::class);
    $settings->two_factor_enabled = true;
    $settings->whitelist_active = false;
    $settings->save();
    $user = User::factory()->create(['two_factor_required' => true]);
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
    $this->actingAs($user);

    $this->get(route('filament.'.$panel.'.auth.profile'))->assertOk();
})->with(['app', 'admin']);

test('shows the security tab and authenticator section only when globally enabled', function (string $panel, bool $enabled) {
    $settings = app(LoginSettings::class);
    $settings->two_factor_enabled = $enabled;
    $settings->save();
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel($panel));

    $component = Livewire::test(EditProfile::class);

    if ($enabled) {
        $component->assertSee('Security')
            ->assertSee(__('settings.two_factor_title'))
            ->assertSee(__('settings.two_factor_description'));
    } else {
        $component->assertDontSee('Security')
            ->assertDontSee(__('settings.two_factor_title'));
    }
})->with(['app', 'admin'])->with([true, false]);

test('saves notification preferences independently of the security tab', function () {
    $settings = app(LoginSettings::class);
    $settings->two_factor_enabled = true;
    $settings->save();
    $user = User::factory()->create();
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(EditProfile::class)
        ->fillForm(['notification_email' => 'notifications@example.com'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->fresh()->notification_email)->toBe('notifications@example.com');
    expect($user->fresh()->getAppAuthenticationSecret())->toBeNull();
});

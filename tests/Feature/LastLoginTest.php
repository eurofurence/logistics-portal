<?php

use App\Filament\Pages\Auth\Login;
use App\Models\Permission;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

test('records the last login after a successful password login', function (string $panel, ?string $lastLogin) {
    $this->freezeSecond();
    Filament::setCurrentPanel(Filament::getPanel($panel));
    $user = User::factory()->create(['last_login' => $lastLogin]);
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'password'], 'form')
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('users', ['id' => $user->id, 'last_login' => now()->toDateTimeString()]);
})->with(['app', 'admin'])->with([
    'first login' => null,
    'subsequent login' => '2025-01-01 12:00:00',
]);

test('preserves the last login when the password is incorrect', function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    $user = User::factory()->create(['last_login' => '2025-01-01 12:00:00']);

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'wrong-password'], 'form')
        ->call('authenticate')
        ->assertHasErrors(['data.email' => __('filament-panels::auth/pages/login.messages.failed')]);

    $this->assertGuest();
    $this->assertDatabaseHas('users', ['id' => $user->id, 'last_login' => '2025-01-01 12:00:00']);
});

test('preserves the last login when panel access is denied', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $user = User::factory()->create(['last_login' => null]);

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'password'], 'form')
        ->call('authenticate')
        ->assertHasErrors(['data.email' => __('filament-panels::auth/pages/login.messages.failed')]);

    $this->assertGuest();
    $this->assertDatabaseHas('users', ['id' => $user->id, 'last_login' => null]);
});

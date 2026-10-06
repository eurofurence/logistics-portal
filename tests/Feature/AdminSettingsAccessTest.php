<?php

use App\Filament\Admin\Pages\ManageGeneral;
use App\Filament\Admin\Pages\ManageLogin;
use App\Filament\Admin\Pages\ManageTheme;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

dataset('admin settings pages', [
    'general' => [ManageGeneral::class, 'access-general-settings'],
    'theme' => [ManageTheme::class, 'access-theme-settings'],
    'login' => [ManageLogin::class, 'access-login-settings'],
]);

test('allows settings access only with both panel and page permissions', function (string $page, string $permission, string $panel, bool $adminAccess, bool $settingsAccess, bool $allowed) {
    $user = User::factory()->create();
    if ($adminAccess) {
        $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    }
    if ($settingsAccess) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel($panel));

    expect($page::canAccess())->toBe($allowed);
    expect((new $page)->canEdit())->toBe($allowed);
})->with('admin settings pages')->with([
    'authorized' => ['admin', true, true, true],
    'missing settings permission' => ['admin', true, false, false],
    'missing admin permission' => ['admin', false, true, false],
    'wrong panel' => ['app', true, true, false],
]);

test('denies direct Livewire access without the settings permission', function (string $page, string $permission) {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test($page)->assertForbidden();
})->with('admin settings pages');

test('rechecks settings permissions before saving an already opened page', function (string $page, string $permission) {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'), Permission::findOrCreate($permission, 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $component = Livewire::test($page);
    $settings = app($page::getSettings());
    $original = $settings->toArray();
    $user->revokePermissionTo($permission);

    $component->call('save')->assertForbidden();

    expect($settings->refresh()->toArray())->toBe($original);
})->with('admin settings pages');

test('retains settings access for the Master role', function (string $page, string $permission) {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('Master', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test($page)->assertOk();
})->with('admin settings pages');

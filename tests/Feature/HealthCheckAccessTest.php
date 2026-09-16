<?php

use App\Filament\Admin\Pages\HealthCheckResults;
use App\Models\Permission;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Spatie\Health\Commands\RunHealthChecksCommand;

test('allows authorized administrators to refresh health checks through Livewire', function () {
    $user = User::factory()->create();
    foreach (['access-adminpanel', 'access-healthchecks'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Artisan::shouldReceive('call')->once()->with(RunHealthChecksCommand::class)->andReturn(0);

    Livewire::test(HealthCheckResults::class)
        ->call('refresh')
        ->assertDispatched('refresh-component')
        ->assertNotified();
});

test('denies health checks outside the admin panel or without the required permissions', function (string $panel, array $permissions) {
    $user = User::factory()->create();
    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel($panel));
    Artisan::shouldReceive('call')->never();

    Livewire::test(HealthCheckResults::class)->assertForbidden();
})->with([
    'missing health permission' => ['admin', ['access-adminpanel']],
    'missing admin access' => ['admin', ['access-healthchecks']],
    'wrong panel' => ['app', ['access-adminpanel', 'access-healthchecks']],
]);

test('rechecks authorization when refreshing an already opened health page', function () {
    $user = User::factory()->create();
    foreach (['access-adminpanel', 'access-healthchecks'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $page = Livewire::test(HealthCheckResults::class);
    $user->revokePermissionTo('access-healthchecks');
    Artisan::shouldReceive('call')->never();

    $page->call('refresh')->assertForbidden();
});

test('denies health access without an authenticated user', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    expect(HealthCheckResults::canAccess())->toBeFalse();
});

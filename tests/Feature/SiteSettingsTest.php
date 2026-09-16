<?php

use App\Filament\Admin\Pages\ManageGeneral;
use App\Filament\Admin\Pages\ManageTheme;
use App\Models\Permission;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Settings\LoginSettings;
use App\Settings\ThemeSettings;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('allows administrators to save a timezone and rejects invalid identifiers', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(ManageGeneral::class)
        ->fillForm(['timezone' => 'America/New_York'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(GeneralSettings::class)->refresh()->timezone)->toBe('America/New_York');

    Livewire::test(ManageGeneral::class)
        ->fillForm(['timezone' => 'Invalid/Timezone'])
        ->call('save')
        ->assertHasFormErrors(['timezone']);

    expect(app(GeneralSettings::class)->refresh()->timezone)->toBe('America/New_York');
});

test('uploads a site logo and restores the default logo when removed', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(ManageTheme::class)
        ->fillForm(['logo' => UploadedFile::fake()->image('logo.png')])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(ThemeSettings::class)->refresh();
    Storage::disk('public')->assertExists($settings->logo);
    expect(view('vendor.filament-panels.components.logo')->render())->toContain(Storage::disk('public')->url($settings->logo));

    Livewire::test(ManageTheme::class)
        ->fillForm(['logo' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(ThemeSettings::class)->refresh()->logoUrl())->toBe(asset('images/logo-round-filled.png'));
});

test('denies settings access to users without admin panel permission', function (string $page) {
    $this->actingAs(User::factory()->create());
    LoginSettings::fake(['whitelist_active' => false]);

    $this->get($page::getUrl(panel: 'admin'))->assertForbidden();
    expect($page::canAccess())->toBeFalse();
    expect(app($page)->canEdit())->toBeFalse();
})->with([[ManageGeneral::class], [ManageTheme::class]]);

test('requires authentication for settings pages', function (string $page) {
    $this->get($page::getUrl(panel: 'admin'))->assertRedirect();
})->with([[ManageGeneral::class], [ManageTheme::class]]);

test('rejects non image logo uploads', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(ManageTheme::class)
        ->fillForm(['logo' => UploadedFile::fake()->create('logo.txt', 1, 'text/plain')])
        ->call('save')
        ->assertHasFormErrors(['logo']);

    expect(app(ThemeSettings::class)->refresh()->logo)->toBeNull();
});

<?php

use App\Filament\Pages\Auth\EditProfile;
use App\Models\Permission;
use App\Models\User;
use App\Settings\LoginSettings;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Symfony\Component\DomCrawler\Crawler;

test('opens the authenticator setup dialog from the profile security tab', function (string $panel) {
    $settings = app(LoginSettings::class);
    $settings->two_factor_enabled = true;
    $settings->passkeys_enabled = false;
    $settings->whitelist_active = false;
    $settings->save();
    $user = User::factory()->create(['two_factor_required' => false]);
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel($panel));

    $component = Livewire::test(EditProfile::class);
    $component->assertSee('wire:partial="action-modals"', false)
        ->mountAction(TestAction::make('setUpAppAuthentication')->schemaComponent('app', 'content'));

    $component->assertHasNoErrors()
        ->assertActionMounted(TestAction::make('setUpAppAuthentication')->schemaComponent('app', 'content'));
    expect($component->effects['partials']['action-modals'])->toContain('fi-modal', 'data:image/svg+xml');
})->with(['app', 'admin']);

test('starts passkey registration from the component embedded in the profile', function (string $panel) {
    $settings = app(LoginSettings::class);
    $settings->passkeys_enabled = true;
    $settings->two_factor_enabled = false;
    $settings->whitelist_active = false;
    $settings->save();
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $this->actingAs($user);
    $profile = $this->get(route('filament.'.$panel.'.auth.profile'))->assertOk();
    $document = new Crawler($profile->getContent());
    $snapshots = $document->filter('[wire\\:snapshot]')->each(fn (Crawler $node): string => $node->attr('wire:snapshot'));
    $snapshot = collect($snapshots)->first(fn (string $snapshot): bool => str_contains(json_decode($snapshot, true)['memo']['name'], 'passkeys'));
    expect($snapshot)->toBeString();

    $response = $this->postJson(route('default.livewire.update'), [
        'components' => [[
            'snapshot' => $snapshot,
            'updates' => ['name' => 'Laptop'],
            'calls' => [['method' => 'validatePasskeyProperties', 'params' => [], 'path' => '']],
        ]],
    ], ['X-Livewire' => 'true']);

    $response->assertOk()
        ->assertJsonPath('components.0.effects.dispatches.0.name', 'passkeyPropertiesValidated');
})->with(['app', 'admin']);

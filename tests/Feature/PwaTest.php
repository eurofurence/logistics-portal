<?php

use App\Services\PwaConfiguration;
use App\Settings\GeneralSettings;
use Filament\Facades\Filament;

test('exposes installation metadata and service worker registration in the app panel', function () {
    $appPanel = Filament::getPanel('app');
    Filament::setCurrentPanel($appPanel);

    $this->get($appPanel->getLoginUrl())->assertSuccessful()
        ->assertSee('rel="manifest"', false)
        ->assertSee('/pwa/manifest.json?lang=', false)
        ->assertSee('/register-service-worker.js?v=', false)
        ->assertSee('data-worker-url="/sw.js?v=', false);
});

test('does not register the app service worker in the admin panel', function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    expect(view('components.pwa')->render())->toContain('rel="manifest"');

    $adminPanel = Filament::getPanel('admin');
    Filament::setCurrentPanel($adminPanel);
    expect(trim(view('components.pwa')->render()))->toBe('');

    $this->get($adminPanel->getLoginUrl())->assertSuccessful()
        ->assertDontSee('rel="manifest"', false)
        ->assertDontSee('/register-service-worker.js', false);
});

test('ships an installable manifest with matching app icons and a reachable start route', function () {
    $manifest = $this->get(route('pwa.manifest'))->assertSuccessful()->json();

    expect($manifest['display'])->toBe('standalone');
    expect($manifest['start_url'])->toStartWith($manifest['scope']);
    expect($manifest['icons'])->toHaveCount(2);

    foreach ($manifest['icons'] as $icon) {
        $dimensions = getimagesize(public_path(ltrim($icon['src'], '/')));
        expect($dimensions[0].'x'.$dimensions[1])->toBe($icon['sizes']);
        expect($dimensions['mime'])->toBe($icon['type']);
    }

    $this->get($manifest['start_url'])->assertRedirect(Filament::getPanel('app')->getLoginUrl());
});

test('uses the configured portal name and requested language in the installation manifest', function (string $locale, string $description) {
    $settings = app(GeneralSettings::class);
    $settings->site_name = 'My Event Portal';
    $settings->save();

    $this->get(route('pwa.manifest', ['lang' => $locale]))->assertSuccessful()
        ->assertJsonPath('name', 'My Event Portal')
        ->assertJsonPath('short_name', 'My Event Portal')
        ->assertJsonPath('lang', $locale)
        ->assertJsonPath('description', $description);
})->with([
    ['de', 'Dein Portal, überall dabei.'],
    ['en', 'Your portal, wherever you are.'],
]);

test('uses Portal when no portal name is configured', function () {
    $settings = app(GeneralSettings::class);
    $settings->site_name = null;
    $settings->save();

    $this->get(route('pwa.manifest'))->assertSuccessful()->assertJsonPath('name', 'Portal');
});

test('uses Portal when settings cannot be read', function () {
    app()->bind(GeneralSettings::class, function (): never {
        throw new RuntimeException('Settings unavailable');
    });

    expect(app(PwaConfiguration::class)->name())->toBe('Portal');
});

test('embeds translated offline texts and safely escapes the configured name', function () {
    app(GeneralSettings::class)->site_name = 'Portal <script>alert(1)</script>';
    Filament::setCurrentPanel(Filament::getPanel('app'));
    app()->setLocale('de');

    $html = view('components.pwa')->render();

    expect($html)->not->toContain('<script>alert(1)</script>');
    preg_match('/data-offline-configuration="([^"]*)"/', $html, $matches);
    $configuration = json_decode(html_entity_decode($matches[1], ENT_QUOTES), true, flags: JSON_THROW_ON_ERROR);
    expect($configuration['name'])->toBe('Portal <script>alert(1)</script>');
    expect($configuration['locale'])->toBe('de');
    expect($configuration['translations']['de']['title'])->toBe('Du bist gerade offline');
    expect($configuration['translations']['en']['title'])->toBe('You are offline');
});

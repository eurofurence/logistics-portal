<?php

use App\Settings\ThemeSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('serves the configured private logo from the configured storage disk to guests', function () {
    config(['filesystems.default' => 's3']);
    Storage::fake('s3');
    $contents = UploadedFile::fake()->image('logo.png')->get();
    Storage::disk('s3')->put('site_logo/logo.png', $contents, 'private');
    $settings = app(ThemeSettings::class);
    $settings->logo = 'site_logo/logo.png';
    $settings->save();

    $this->get(route('branding.logo'))->assertSuccessful()
        ->assertHeader('Content-Type', 'image/png')->assertStreamedContent($contents);

    Storage::disk('s3')->assertExists('site_logo/logo.png');
    expect(view('vendor.filament-panels.components.logo')->render())->toContain('/branding/logo?v=');
});

test('renders a heroicon instead of an image when no logo is configured', function () {
    $settings = app(ThemeSettings::class);
    $settings->logo = null;
    $settings->save();

    $html = view('vendor.filament-panels.components.logo')->render();

    expect($html)->toContain('<svg')->not->toContain('<img');
    $this->getJson(route('branding.logo'))->assertNotFound();
});

test('returns 404 for a missing configured logo so the browser can show the fallback icon', function () {
    Storage::fake('local');
    config(['filesystems.default' => 'local']);
    $settings = app(ThemeSettings::class);
    $settings->logo = 'site_logo/missing.png';
    $settings->save();

    $this->getJson(route('branding.logo'))->assertNotFound();

    Storage::disk('local')->assertMissing('site_logo/missing.png');
});

test('renders the fallback icon when theme settings are unavailable', function () {
    app()->bind(ThemeSettings::class, function (): never {
        throw new RuntimeException('Settings unavailable');
    });

    $html = view('vendor.filament-panels.components.logo')->render();

    expect($html)->toContain('<svg')->not->toContain('<img');
});

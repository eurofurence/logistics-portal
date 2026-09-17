<?php

use App\Filament\Admin\Pages\ManageGeneral;
use App\Filament\Admin\Pages\ManageTheme;
use App\Models\Permission;
use App\Models\User;
use App\Notifications\GeneralNotification;
use App\Notifications\OrderApprovalReminder;
use App\Settings\GeneralSettings;
use App\Settings\LoginSettings;
use App\Settings\ThemeSettings;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('allows administrators to select an available default language', function (string $locale) {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(ManageGeneral::class)
        ->assertFormFieldExists('default_locale', fn (Select $field): bool => $field->getOptions() === ['en' => 'English', 'de' => 'Deutsch'])
        ->fillForm(['default_locale' => $locale])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(GeneralSettings::class)->refresh()->default_locale)->toBe($locale);
})->with(['en', 'de']);

test('rejects missing and unavailable default languages without changing the saved language', function (?string $locale, string $rule) {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(ManageGeneral::class)
        ->fillForm(['default_locale' => $locale])
        ->call('save')
        ->assertHasFormErrors(['default_locale' => $rule]);

    expect(app(GeneralSettings::class)->refresh()->default_locale)->toBe('en');
})->with([
    'missing' => [null, 'required'],
    'unavailable' => ['fr', 'in'],
]);

test('uses the current default language for visitors in both panels', function (string $panel) {
    $settings = app(GeneralSettings::class);
    $settings->default_locale = 'de';
    $settings->save();

    $this->get(Filament::getPanel($panel)->getLoginUrl())
        ->assertSee('lang="de"', false);

    $settings->default_locale = 'en';
    $settings->save();

    $this->get(Filament::getPanel($panel)->getLoginUrl())
        ->assertSee('lang="en"', false);
})->with(['admin', 'app']);

test('keeps the personal session language ahead of the default language', function (string $panel) {
    GeneralSettings::fake(['default_locale' => 'de']);

    $this->withSession(['locale' => 'en'])
        ->get(Filament::getPanel($panel)->getLoginUrl())
        ->assertSee('lang="en"', false);
})->with(['admin', 'app']);

test('keeps the remembered cookie language ahead of the default language', function (string $panel) {
    GeneralSettings::fake(['default_locale' => 'de']);

    $this->withCookie('filament_language_switcher_locale', 'en')
        ->get(Filament::getPanel($panel)->getLoginUrl())
        ->assertSee('lang="en"', false)
        ->assertSessionHas('locale', 'en');
})->with(['admin', 'app']);

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
    config(['filesystems.default' => 'site-assets']);
    Storage::fake('site-assets');
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(ManageTheme::class)
        ->fillForm(['logo' => UploadedFile::fake()->image('logo.png')])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(ThemeSettings::class)->refresh();
    expect($settings->logo)->toStartWith('site_logo/');
    Storage::disk('site-assets')->assertExists($settings->logo);
    expect(view('vendor.filament-panels.components.logo')->render())->toContain(Storage::disk('site-assets')->url($settings->logo));

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
    config(['filesystems.default' => 'site-assets']);
    Storage::fake('site-assets');
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

test('saves site identity and seo settings and escapes public metadata', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(ManageGeneral::class)
        ->fillForm([
            'site_name' => 'Logistics & Events',
            'site_description' => 'Events " worldwide <script>alert(1)</script>',
            'seo_keywords' => 'logistics, events',
            'search_engine_indexing' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(GeneralSettings::class)->refresh();
    expect($settings->displayName())->toBe('Logistics & Events')
        ->and($settings->seo_keywords)->toBe('logistics, events')
        ->and($settings->search_engine_indexing)->toBeTrue();
    expect(view('components.site-meta')->render())->toContain('noindex, nofollow');

    auth()->logout();
    $this->get(Filament::getPanel('app')->getLoginUrl())
        ->assertOk()
        ->assertSee('Logistics &amp; Events', false)
        ->assertSee('Events &quot; worldwide &lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertSee('content="index, follow"', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('excludes the admin login from indexing when public indexing is enabled', function () {
    GeneralSettings::fake(['search_engine_indexing' => true]);

    $this->get(Filament::getPanel('admin')->getLoginUrl())
        ->assertOk()
        ->assertSee('content="noindex, nofollow"', false);
});

test('uploads site icons and preview images and restores their defaults', function () {
    config(['filesystems.default' => 'site-assets']);
    Storage::fake('site-assets');
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(ManageTheme::class)
        ->fillForm([
            'favicon' => UploadedFile::fake()->image('icon.png'),
            'social_image' => UploadedFile::fake()->image('preview.jpg', 1200, 630),
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(ThemeSettings::class)->refresh();
    expect($settings->favicon)->toStartWith('site_icon/')
        ->and($settings->social_image)->toStartWith('site_social/')
        ->and(Filament::getPanel('admin')->getFavicon())->toBe($settings->faviconUrl())
        ->and(Filament::getPanel('app')->getFavicon())->toBe($settings->faviconUrl());
    Storage::disk('site-assets')->assertExists([$settings->favicon, $settings->social_image]);
    expect(view('components.site-meta')->render())->toContain($settings->socialImageUrl());

    Livewire::test(ManageTheme::class)
        ->fillForm(['favicon' => null, 'social_image' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings->refresh();
    expect($settings->faviconUrl())->toBe(asset('favicon.ico'))
        ->and($settings->socialImageUrl())->toBe($settings->logoUrl());
});

test('uses the configured application name when the site name is empty', function () {
    GeneralSettings::fake(['site_name' => null]);

    expect(app(GeneralSettings::class)->displayName())->toBe(config('app.name'));
});

test('renders emails using the current primary color at send time', function (string $type) {
    $user = User::factory()->make();
    ThemeSettings::fake(['primary_color' => 'rgb(32, 64, 96)']);
    $notification = $type === 'general'
        ? new GeneralNotification(username: $user->name, details_title: 'Bill', details_link: 'https://example.com', details_link_title: 'Show')
        : new OrderApprovalReminder(new Collection);

    $html = (string) $notification->toMail($user)->render();

    expect($html)->toContain('background-color: #204060;')->not->toContain('#045350');

    ThemeSettings::fake(['primary_color' => 'rgb(255, 255, 0)']);
    $updatedHtml = (string) $notification->toMail($user)->render();

    expect($updatedHtml)->toContain('background-color: #ffff00;')->toContain('color: #000000;')->not->toContain('#204060');
})->with(['general', 'approval digest']);

test('normalizes email colors and selects a readable foreground', function (string $color, string $expected, string $foreground) {
    ThemeSettings::fake(['primary_color' => $color]);

    $colors = app(ThemeSettings::class)->emailColors();

    expect($colors['primary'])->toBe($expected);
    expect($colors['foreground'])->toBe($foreground);
})->with([
    'rgb' => ['rgb(32, 64, 96)', '#204060', '#ffffff'],
    'hex' => ['#ABCDEF', '#abcdef', '#000000'],
    'short hex' => ['#fff', '#ffffff', '#000000'],
    'invalid rgb' => ['rgb(999, 0, 0)', '#01504b', '#ffffff'],
    'invalid css' => ['red; background-image: url(https://example.com)', '#01504b', '#ffffff'],
]);

test('sends the theme test email only to the signed in administrator using saved settings', function () {
    Notification::fake();
    $user = User::factory()->create(['notification_email' => 'preview@example.com']);
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $settings = app(ThemeSettings::class);
    $settings->primary_color = 'rgb(32, 64, 96)';
    $settings->save();

    Livewire::test(ManageTheme::class)
        ->fillForm(['primary_color' => 'rgb(255, 255, 0)'])
        ->callAction(TestAction::make('sendTestEmail')->schemaComponent())
        ->assertNotified(__('settings.test_email_sent'));

    Notification::assertSentTo($user, GeneralNotification::class, function (GeneralNotification $notification, array $channels) use ($user): bool {
        expect((string) $notification->toMail($user)->render())->toContain('background-color: #204060;');
        expect($user->routeNotificationForMail($notification))->toBe('preview@example.com');

        return $channels === ['mail'];
    });
    Notification::assertCount(1);
    expect(app(ThemeSettings::class)->refresh()->primary_color)->toBe('rgb(32, 64, 96)');
});

test('shows a failure notice when the test email cannot be sent', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-adminpanel', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Notification::shouldReceive('sendNow')->once()->andThrow(new RuntimeException('Mail unavailable'));

    Livewire::test(ManageTheme::class)
        ->callAction(TestAction::make('sendTestEmail')->schemaComponent())
        ->assertNotified(__('settings.test_email_failed'));
});

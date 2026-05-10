<?php

namespace App\Providers\Filament;

use Althinect\FilamentSpatieRolesPermissions\FilamentSpatieRolesPermissionsPlugin;
use App\Filament\Admin\Pages\HealthCheckResults;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use App\Http\Middleware\CheckWhitelist;
use App\Http\Middleware\UserIsLocked;
use App\Settings\ThemeSettings;
use Awcodes\Versions\VersionsPlugin;
use Awcodes\Versions\VersionsWidget;
use CharrafiMed\GlobalSearchModal\GlobalSearchModalPlugin;
use CraftForge\FilamentLanguageSwitcher\FilamentLanguageSwitcherPlugin;
use Exception;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard as FilamentDashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Njxqlus\FilamentProgressbar\FilamentProgressbarPlugin;
use pxlrbt\FilamentSpotlight\SpotlightPlugin;
use ShuvroRoy\FilamentSpatieLaravelHealth\FilamentSpatieLaravelHealthPlugin;
use TomatoPHP\FilamentDeveloperGate\FilamentDeveloperGatePlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        try {
            $primaryColor = app(ThemeSettings::class)->primary_color;
        } catch (Exception $e) {
            // Set an alternative value if an error occurs
            $primaryColor = '#007bff'; // Example: Standard blue color
        }

        return $panel
            ->id('admin')
            ->path('admin')
            ->favicon(asset('favicon.ico'))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => $primaryColor,
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                FilamentDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->widgets([
                AccountWidget::class,
                VersionsWidget::class,
            ])
            ->login(Login::class)
            //->passwordReset()
            ->profile(EditProfile::class, false)
            //->emailVerification()
            ->unsavedChangesAlerts()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->sidebarCollapsibleOnDesktop()
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                VersionsPlugin::make()
                    ->widgetSort(1),
                FilamentLanguageSwitcherPlugin::make()
                    ->rememberLocale(days: 30)
                    ->locales(['en', 'de']),
                FilamentProgressbarPlugin::make()->color('#29b'),
                SpatieTranslatablePlugin::make()
                    ->defaultLocales(['en', 'de']),
                FilamentSpatieLaravelHealthPlugin::make()
                    ->usingPage(HealthCheckResults::class),
                //FilamentUserActivityPlugin::make(),
                SpotlightPlugin::make(),
                GlobalSearchModalPlugin::make(),
                FilamentDeveloperGatePlugin::make(),
            ])
            ->navigationItems([
                NavigationItem::make('app')
                    ->label(__('general.app'))
                    ->url('/app', shouldOpenInNewTab: false)
                    ->icon('heroicon-o-chevron-double-left')
                    ->sort(0),
            ])
            ->plugin(FilamentSpatieRolesPermissionsPlugin::make())
            ->authMiddleware([
                Authenticate::class,
                UserIsLocked::class,
                CheckWhitelist::class,
            ]);
    }
}

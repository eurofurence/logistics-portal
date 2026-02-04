<?php

namespace App\Providers\Filament;

use Exception;
use Filament\Panel;
use Filament\PanelProvider;
use App\Settings\ThemeSettings;
use App\Filament\Pages\Auth\Login;
use Filament\Widgets\AccountWidget;
use App\Http\Middleware\UserIsLocked;
use App\Http\Middleware\CheckWhitelist;
use Filament\Navigation\NavigationItem;
use App\Filament\Pages\Auth\EditProfile;
use Filament\Http\Middleware\Authenticate;
use pxlrbt\FilamentSpotlight\SpotlightPlugin;
use Illuminate\Session\Middleware\StartSession;
use App\Filament\Admin\Pages\HealthCheckResults;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Pages\Dashboard as FilamentDashboard;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use CharrafiMed\GlobalSearchModal\GlobalSearchModalPlugin;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Njxqlus\FilamentProgressbar\FilamentProgressbarPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use TomatoPHP\FilamentDeveloperGate\FilamentDeveloperGatePlugin;
use CraftForge\FilamentLanguageSwitcher\FilamentLanguageSwitcherPlugin;
use ShuvroRoy\FilamentSpatieLaravelHealth\FilamentSpatieLaravelHealthPlugin;
use Althinect\FilamentSpatieRolesPermissions\FilamentSpatieRolesPermissionsPlugin;

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
            //->viteTheme('resources/css/filament/admin/theme.css')
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
                FilamentLanguageSwitcherPlugin::make()
                    ->locales([
                        ['code' => 'en', 'flag' => 'us'],
                        ['code' => 'de'],
                    ]),
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

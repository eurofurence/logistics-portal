<?php

namespace App\Providers\Filament;

use Exception;
use Filament\Panel;
use Filament\PanelProvider;
use App\Settings\ThemeSettings;
use App\Filament\Pages\Dashboard;
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
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use CharrafiMed\GlobalSearchModal\GlobalSearchModalPlugin;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Njxqlus\FilamentProgressbar\FilamentProgressbarPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use TomatoPHP\FilamentDeveloperGate\FilamentDeveloperGatePlugin;
use CraftForge\FilamentLanguageSwitcher\FilamentLanguageSwitcherPlugin;
use ShuvroRoy\FilamentSpatieLaravelHealth\FilamentSpatieLaravelHealthPlugin;

class AppPanelProvider extends PanelProvider
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
            ->id('app')
            ->path('app')
            ->colors([
                'primary' => $primaryColor,
            ])
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\\Filament\\App\\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\\Filament\\App\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\\Filament\\App\\Widgets')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            //->viteTheme('resources/css/filament/app/theme.css')
            ->widgets([
                AccountWidget::class,
            ])
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
                FilamentSpatieLaravelHealthPlugin::make()
                    ->usingPage(HealthCheckResults::class),
                SpotlightPlugin::make(),
                GlobalSearchModalPlugin::make(),
                FilamentDeveloperGatePlugin::make()
                /*
                FilamentSentryFeedbackPlugin::make()
                    ->sentryUser(function (): ?SentryUser {
                        return new SentryUser(auth()->user()->name, auth()->user()->email);
                    })
                */
            ])
            ->unsavedChangesAlerts()
            ->authMiddleware([
                Authenticate::class,
                UserIsLocked::class,
                CheckWhitelist::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->sidebarCollapsibleOnDesktop()
            ->navigationItems([
                NavigationItem::make('dashboard')
                    ->label(__('general.dashboard'))
                    ->url('https://identity.eurofurence.org', shouldOpenInNewTab: false)
                    ->icon('heroicon-o-chevron-double-left')
                    ->sort(0),
                NavigationItem::make('admin_panel')
                    ->label(__('general.admin_panel'))
                    ->url('/admin') // Pfad zu deinem Admin-Panel
                    ->icon('heroicon-o-cog')
                    // Der Button wird nur angezeigt, wenn der User die Berechtigung hat
                    ->visible(fn(): bool => auth()->user()?->can('access-adminpanel') ?? false)
                    ->sort(100), // Ganz nach unten in der Liste
            ])
            ->login(Login::class)
            //->passwordReset()
            //->emailVerification()
            //->registration()
            ->profile(EditProfile::class, false)
            ->default();
    }
}

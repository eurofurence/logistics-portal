<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\HealthCheckResults;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Http\Middleware\CheckWhitelist;
use App\Http\Middleware\UserIsLocked;
use App\Settings\ThemeSettings;
use CharrafiMed\GlobalSearchModal\GlobalSearchModalPlugin;
use CraftForge\FilamentLanguageSwitcher\FilamentLanguageSwitcherPlugin;
use Exception;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
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
use MartinPetricko\FilamentSentryFeedback\Entities\SentryUser;
use MartinPetricko\FilamentSentryFeedback\FilamentSentryFeedbackPlugin;
use Njxqlus\FilamentProgressbar\FilamentProgressbarPlugin;
use pxlrbt\FilamentSpotlight\SpotlightPlugin;
use SalmanAlmajali\JokesWidget\JokesWidget;
use ShuvroRoy\FilamentSpatieLaravelHealth\FilamentSpatieLaravelHealthPlugin;
use TomatoPHP\FilamentDeveloperGate\FilamentDeveloperGatePlugin;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->colors(function (): array {
                try {
                    $primaryColor = app(ThemeSettings::class)->primary_color;
                } catch (Exception $e) {
                    $primaryColor = '#007bff';
                }

                return ['primary' => $primaryColor];
            })
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\\Filament\\App\\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\\Filament\\App\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\\Filament\\App\\Widgets')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            // ->viteTheme('resources/css/filament/app/theme.css')
            ->widgets([
                AccountWidget::class,
                // JokesWidget::make(),
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
                    ->rememberLocale(days: 30)
                    ->locales(['en', 'de']),
                FilamentProgressbarPlugin::make()->color('#29b'),
                FilamentSpatieLaravelHealthPlugin::make()
                    ->usingPage(HealthCheckResults::class),
                SpotlightPlugin::make(),
                GlobalSearchModalPlugin::make(),
                FilamentDeveloperGatePlugin::make(),
                FilamentSentryFeedbackPlugin::make()
                    ->sentryUser(function (): ?SentryUser {
                        return new SentryUser(auth()->user()->name, auth()->user()->email);
                    })
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
                    ->visible(fn (): bool => auth()->user()?->can('access-adminpanel') ?? false)
                    ->sort(100), // Ganz nach unten in der Liste
            ])
            ->login(Login::class)
            ->passwordReset()
            // ->emailVerification()
            // ->registration()
            ->profile(EditProfile::class, false)
            ->default();
    }
}

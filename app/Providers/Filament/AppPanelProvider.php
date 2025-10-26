<?php

namespace App\Providers\Filament;

use Exception;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use App\Settings\ThemeSettings;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Auth\Login;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\UserIsLocked;
use App\Http\Middleware\CheckWhitelist;
use Filament\Navigation\NavigationItem;
use App\Filament\Pages\Auth\EditProfile;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Awcodes\QuickCreate\QuickCreatePlugin;
use Filament\Http\Middleware\Authenticate;
use App\Filament\App\Resources\Bills\BillResource;
use App\Filament\App\Resources\Items\ItemResource;
use App\Filament\App\Resources\Orders\OrderResource;
use pxlrbt\FilamentSpotlight\SpotlightPlugin;
use App\Filament\App\Resources\Storages\StorageResource;
use Illuminate\Session\Middleware\StartSession;
use App\Filament\Admin\Pages\HealthCheckResults;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Filament\App\Resources\OrderEvents\OrderEventResource;
use App\Filament\App\Resources\OrderArticles\OrderArticleResource;
use App\Filament\App\Resources\OrderRequests\OrderRequestResource;
use App\Filament\App\Resources\OrderCategories\OrderCategoryResource;
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
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;
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
                SpatieTranslatablePlugin::make()
                    ->defaultLocales(['en', 'de']),
                FilamentProgressbarPlugin::make()->color('#29b'),
                FilamentSpatieLaravelHealthPlugin::make()
                    ->usingPage(HealthCheckResults::class),
                QuickCreatePlugin::make()
                    ->includes([
                        OrderArticleResource::class,
                        OrderEventResource::class,
                        OrderCategoryResource::class,
                        OrderResource::class,
                        OrderRequestResource::class,
                        BillResource::class,
                        StorageResource::class,
                        ItemResource::class,
                    ]),
                EnvironmentIndicatorPlugin::make()
                    ->visible(fn() => match (config('app.env')) {
                        'production' => false,
                        'local' => true,
                        'testing' => true,
                    })
                    ->color(fn() => match (config('app.env')) {
                        'production' => null,
                        'local' => Color::Pink,
                        'testing' => Color::Orange,
                        default => Color::Blue,
                    }),
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
            ])
            ->login(Login::class)
            //->passwordReset()
            //->emailVerification()
            //->registration()
            ->profile(EditProfile::class, false)
            ->bootUsing(function () {
                PanelSwitch::configureUsing(function (PanelSwitch $panelSwitch) {
                    $panelSwitch
                        ->modalHeading(__('general.application'))
                        ->labels([
                            'admin' => __('general.admin_panel'),
                            'app' => __('general.logistics'),
                        ])
                        ->icons([
                            'admin' => 'heroicon-o-cog',
                            'app' => 'heroicon-o-truck',
                        ], $asImage = false)
                        ->panels(function (): array {
                            $result = array();

                            $result[] = 'app';

                            if (Auth::user()) {
                                if (Auth::user()->can('access-adminpanel')) {
                                    $result[] = 'admin';
                                }
                            }

                            return $result;
                        })
                        ->visible(function () {
                            if (auth()) {
                                return Auth::user()->can('access-adminpanel');
                            }
                        });
                });
            });
    }
}

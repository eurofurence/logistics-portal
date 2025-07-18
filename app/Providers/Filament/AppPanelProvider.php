<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use App\Settings\ThemeSettings;
use App\Filament\Pages\Dashboard;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\UserIsLocked;
use App\Http\Middleware\CheckWhitelist;
use Filament\Navigation\NavigationItem;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Filament\Http\Middleware\Authenticate;
use App\Filament\App\Resources\BillResource;
use App\Filament\App\Resources\ItemResource;
use App\Filament\App\Resources\OrderResource;
use Filament\SpatieLaravelTranslatablePlugin;
use pxlrbt\FilamentSpotlight\SpotlightPlugin;
use App\Filament\App\Resources\StorageResource;
use Illuminate\Session\Middleware\StartSession;
use App\Filament\Admin\Pages\HealthCheckResults;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Filament\App\Resources\OrderEventResource;
use Awcodes\FilamentQuickCreate\QuickCreatePlugin;
use DutchCodingCompany\FilamentSocialite\Provider;
use App\Filament\App\Resources\OrderArticleResource;
use App\Filament\App\Resources\OrderRequestResource;
use App\Filament\App\Resources\OrderCategoryResource;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saade\FilamentLaravelLog\FilamentLaravelLogPlugin;
use Filament\Http\Middleware\DisableBladeIconComponents;
use CharrafiMed\GlobalSearchModal\GlobalSearchModalPlugin;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Njxqlus\FilamentProgressbar\FilamentProgressbarPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use MartinPetricko\FilamentSentryFeedback\Entities\SentryUser;
use TomatoPHP\FilamentDeveloperGate\FilamentDeveloperGatePlugin;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;
use MartinPetricko\FilamentSentryFeedback\FilamentSentryFeedbackPlugin;
use ShuvroRoy\FilamentSpatieLaravelHealth\FilamentSpatieLaravelHealthPlugin;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        try {
            $primaryColor = app(ThemeSettings::class)->primary_color;
        } catch (\Exception $e) {
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
                Widgets\AccountWidget::class,
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
                FilamentSocialitePlugin::make()
                    // (required) Add providers corresponding with providers in `config/services.php`.
                    ->providers([
                        Provider::make('identity')
                            ->label('EF Identity')
                            ->icon('heroicon-o-identification')
                            ->color(Color::Emerald)
                            ->outlined(false)
                            ->stateless(false)
                    ])
                    ->registration(true),
                SpatieLaravelTranslatablePlugin::make()
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
            ->sidebarCollapsibleOnDesktop()
            ->navigationItems([
                NavigationItem::make('dashboard')
                    ->label(__('general.dashboard'))
                    ->url('https://identity.eurofurence.org', shouldOpenInNewTab: false)
                    ->icon('heroicon-o-chevron-double-left')
                    ->sort(0),
            ])
            ->login()
            ->passwordReset()
            ->emailVerification()
            //->registration()
            #8 Add profile function #TODO
            //->profile()
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

<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use App\Settings\ThemeSettings;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\UserIsLocked;
use App\Http\Middleware\CheckWhitelist;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Filament\Http\Middleware\Authenticate;
use Filament\SpatieLaravelTranslatablePlugin;
use pxlrbt\FilamentSpotlight\SpotlightPlugin;
use Illuminate\Session\Middleware\StartSession;
use App\Filament\Admin\Pages\HealthCheckResults;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Awcodes\FilamentQuickCreate\QuickCreatePlugin;
use DutchCodingCompany\FilamentSocialite\Provider;
use Filament\Pages\Dashboard as FilamentDashboard;
use App\Filament\Admin\Resources\WhitelistResource;
use App\Filament\Admin\Resources\DepartmentResource;
use App\Filament\Admin\Resources\IdpRankSyncResource;
use Brickx\MaintenanceSwitch\MaintenanceSwitchPlugin;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
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
use Althinect\FilamentSpatieRolesPermissions\FilamentSpatieRolesPermissionsPlugin;

class AdminPanelProvider extends PanelProvider
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
                Widgets\AccountWidget::class,
            ])
            ->login()
            ->passwordReset()
            ->emailVerification()
            ->unsavedChangesAlerts()
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
                MaintenanceSwitchPlugin::make(),
                FilamentProgressbarPlugin::make()->color('#29b'),
                SpatieLaravelTranslatablePlugin::make()
                    ->defaultLocales(['en', 'de']),
                \Mvenghaus\FilamentScheduleMonitor\FilamentPlugin::make(),
                FilamentSpatieLaravelHealthPlugin::make()
                    ->usingPage(HealthCheckResults::class),
                QuickCreatePlugin::make()
                    ->includes([
                        DepartmentResource::class,
                        WhitelistResource::class,
                        IdpRankSyncResource::class,
                        \Althinect\FilamentSpatieRolesPermissions\Resources\PermissionResource::class,
                        \Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource::class,
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
                //FilamentUserActivityPlugin::make(),
                SpotlightPlugin::make(),
                GlobalSearchModalPlugin::make(),
                FilamentDeveloperGatePlugin::make(),
                FilamentSentryFeedbackPlugin::make()
                    ->sentryUser(function (): ?SentryUser {
                        return new SentryUser(auth()->user()->name, auth()->user()->email);
                    })
            ])
            ->plugin(FilamentSpatieRolesPermissionsPlugin::make())
            ->authMiddleware([
                Authenticate::class,
                UserIsLocked::class,
                CheckWhitelist::class,
            ])
            ->bootUsing(function (Panel $panel) {
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

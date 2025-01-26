<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
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
use Filament\Pages\Dashboard as FilamentDashboard;
use App\Filament\Admin\Resources\WhitelistResource;
use App\Filament\Admin\Resources\DepartmentResource;
use App\Filament\Admin\Resources\IdpRankSyncResource;
use Brickx\MaintenanceSwitch\MaintenanceSwitchPlugin;
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
use TomatoPHP\FilamentDeveloperGate\FilamentDeveloperGatePlugin;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;
use ShuvroRoy\FilamentSpatieLaravelHealth\FilamentSpatieLaravelHealthPlugin;
use Althinect\FilamentSpatieRolesPermissions\FilamentSpatieRolesPermissionsPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->favicon(asset('favicon.ico'))
            //->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => Color::Emerald,
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
                FilamentSocialitePlugin::make()
                    // (required) Add providers corresponding with providers in `config/services.php`.
                    ->setProviders([
                        'identity' => [
                            'label' => 'EF Identity',
                            // Custom icon requires an additional package, see below.
                            'icon' => 'heroicon-o-identification',
                            // (optional) Button color override, default: 'gray'.
                            'color' => 'primary',
                        ],
                    ])
                    // (optional) Enable or disable registration from OAuth.
                    ->setRegistrationEnabled(true),
                QuickCreatePlugin::make()
                    ->includes([
                        DepartmentResource::class,
                        WhitelistResource::class,
                        IdpRankSyncResource::class,
                        \Althinect\FilamentSpatieRolesPermissions\Resources\PermissionResource::class,
                        \Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource::class,
                    ]),
                EnvironmentIndicatorPlugin::make()
                    ->visible(fn () => match (config('app.env')) {
                        'production' => false,
                        'local' => true,
                        'testing' => true,
                    })
                    ->color(fn () => match (config('app.env')) {
                        'production' => null,
                        'local' => Color::Pink,
                        'testing' => Color::Orange,
                        default => Color::Blue,
                    }),
                //FilamentUserActivityPlugin::make(),
                SpotlightPlugin::make(),
                FilamentLaravelLogPlugin::make()
                    ->navigationGroup('DEV')
                    ->navigationLabel('Laravel logs')
                    ->navigationIcon('heroicon-o-bug-ant')
                    ->navigationSort(4)
                    ->slug('logs')
                    ->authorize(
                        function () {
                            if (Auth::check()) {
                                return Auth::user()->isSuperAdmin();
                            } else {
                                return false;
                            }
                        }
                    ),
                GlobalSearchModalPlugin::make(),
                FilamentDeveloperGatePlugin::make()
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

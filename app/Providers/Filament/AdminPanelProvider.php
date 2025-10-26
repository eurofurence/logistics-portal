<?php

namespace App\Providers\Filament;

use Exception;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use App\Settings\ThemeSettings;
use App\Filament\Pages\Auth\Login;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\UserIsLocked;
use App\Http\Middleware\CheckWhitelist;
use App\Filament\Pages\Auth\EditProfile;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Awcodes\QuickCreate\QuickCreatePlugin;
use Filament\Http\Middleware\Authenticate;
use pxlrbt\FilamentSpotlight\SpotlightPlugin;
use Illuminate\Session\Middleware\StartSession;
use App\Filament\Admin\Pages\HealthCheckResults;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Pages\Dashboard as FilamentDashboard;
use App\Filament\Admin\Resources\WhitelistResource;
use App\Filament\Admin\Resources\DepartmentResource;
use App\Filament\Admin\Resources\IdpRankSyncResource;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Mvenghaus\FilamentScheduleMonitor\FilamentPlugin;
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
use Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource;
use Althinect\FilamentSpatieRolesPermissions\Resources\PermissionResource;
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
                FilamentProgressbarPlugin::make()->color('#29b'),
                SpatieTranslatablePlugin::make()
                    ->defaultLocales(['en', 'de']),
                FilamentPlugin::make(),
                FilamentSpatieLaravelHealthPlugin::make()
                    ->usingPage(HealthCheckResults::class),
                QuickCreatePlugin::make()
                    ->includes([
                        DepartmentResource::class,
                        WhitelistResource::class,
                        IdpRankSyncResource::class,
                        PermissionResource::class,
                        RoleResource::class,
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

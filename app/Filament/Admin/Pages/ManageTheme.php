<?php

namespace App\Filament\Admin\Pages;

use App\Notifications\GeneralNotification;
use App\Settings\ThemeSettings;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ManageTheme extends SettingsPage
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paint-brush';

    protected static string $settings = ThemeSettings::class;

    public static function getNavigationGroup(): ?string
    {
        return __('general.settings');
    }

    public function getTitle(): string
    {
        return __('settings.theme');
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.theme');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    FileUpload::make('logo')
                        ->label(__('settings.logo'))
                        ->helperText(__('settings.logo_help'))
                        ->disk(fn (): string => config('filesystems.default'))
                        ->directory('site_logo')
                        ->visibility('public')
                        ->image()
                        ->imageEditor()
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->maxSize(2048),
                    FileUpload::make('email_logo')
                        ->label(__('settings.email_logo'))
                        ->helperText(__('settings.email_logo_help'))
                        ->disk(fn (): string => config('filesystems.default'))
                        ->directory('email_logo')
                        ->visibility('public')
                        ->image()
                        ->imageEditor()
                        ->acceptedFileTypes(['image/png', 'image/jpeg'])
                        ->maxSize(2048),
                    FileUpload::make('favicon')
                        ->label(__('settings.favicon'))
                        ->helperText(__('settings.favicon_help'))
                        ->disk(fn (): string => config('filesystems.default'))
                        ->directory('site_icon')
                        ->visibility('public')
                        ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/vnd.microsoft.icon'])
                        ->maxSize(1024),
                    FileUpload::make('social_image')
                        ->label(__('settings.social_image'))
                        ->helperText(__('settings.social_image_help'))
                        ->disk(fn (): string => config('filesystems.default'))
                        ->directory('site_social')
                        ->visibility('public')
                        ->image()
                        ->imageEditor()
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->maxSize(2048),
                    ColorPicker::make('primary_color')
                        ->rgb()
                        ->label(__('settings.primary_color')),
                    Actions::make([
                        Action::make('sendTestEmail')
                            ->label(__('settings.send_test_email'))
                            ->icon('heroicon-o-envelope')
                            ->authorize(fn (): bool => static::canAccess())
                            ->action(function (): void {
                                $user = auth()->user();
                                $notification = new GeneralNotification(
                                    username: $user->name,
                                    subject: __('settings.test_email_subject'),
                                    titel: __('settings.test_email_subject'),
                                    message: __('settings.test_email_message'),
                                    details_title: __('settings.test_email_details'),
                                    details_message: __('settings.test_email_details_message'),
                                    details_link: static::getUrl(panel: 'admin'),
                                    details_link_title: __('general.settings'),
                                );

                                try {
                                    Notification::sendNow($user, $notification, ['mail']);
                                } catch (Throwable $exception) {
                                    report($exception);
                                    FilamentNotification::make()
                                        ->title(__('settings.test_email_failed'))
                                        ->danger()
                                        ->send();

                                    return;
                                }

                                FilamentNotification::make()
                                    ->title(__('settings.test_email_sent'))
                                    ->body($user->routeNotificationForMail($notification))
                                    ->success()
                                    ->send();
                            }),
                    ])->belowContent(__('settings.test_email_help')),
                ]),
            ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessPanel(Filament::getPanel('admin')) ?? false;
    }

    public function canEdit(): bool
    {
        return static::canAccess();
    }
}

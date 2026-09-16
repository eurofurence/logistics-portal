<?php

namespace App\Filament\Admin\Pages;

use App\Settings\ThemeSettings;
use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                        ->disk('public')
                        ->directory('site_logo')
                        ->visibility('public')
                        ->image()
                        ->imageEditor()
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->maxSize(2048),
                    FileUpload::make('favicon')
                        ->label(__('settings.favicon'))
                        ->helperText(__('settings.favicon_help'))
                        ->disk('public')
                        ->directory('site_icon')
                        ->visibility('public')
                        ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/vnd.microsoft.icon'])
                        ->maxSize(1024),
                    FileUpload::make('social_image')
                        ->label(__('settings.social_image'))
                        ->helperText(__('settings.social_image_help'))
                        ->disk('public')
                        ->directory('site_social')
                        ->visibility('public')
                        ->image()
                        ->imageEditor()
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->maxSize(2048),
                    ColorPicker::make('primary_color')
                        ->rgb()
                        ->label(__('settings.primary_color')),
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

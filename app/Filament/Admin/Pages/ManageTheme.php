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
                        ->directory('branding')
                        ->visibility('public')
                        ->image()
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

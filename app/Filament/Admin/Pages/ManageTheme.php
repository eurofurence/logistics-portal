<?php

namespace App\Filament\Admin\Pages;

use Filament\Forms\Form;
use App\Settings\ThemeSettings;
use Filament\Pages\SettingsPage;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\ColorPicker;

class ManageTheme extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make([
                    ColorPicker::make('primary_color')
                        ->rgb()
                        ->label(__('settings.primary_color'))
                ])
            ]);
    }
}

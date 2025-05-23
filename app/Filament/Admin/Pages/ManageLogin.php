<?php

namespace App\Filament\Admin\Pages;

use Filament\Forms\Form;
use App\Settings\LoginSettings;
use Filament\Pages\SettingsPage;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;

class ManageLogin extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static string $settings = LoginSettings::class;

    public static function getNavigationGroup(): ?string
    {
        return __('general.settings');
    }

    public function getTitle(): string
    {
        return __('settings.login');
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.login');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make([
                    Toggle::make('whitelist_active')
                        ->label(__('settings.activate_whitelist'))
                ])
            ]);
    }
}

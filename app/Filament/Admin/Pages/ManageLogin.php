<?php

namespace App\Filament\Admin\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use App\Settings\LoginSettings;
use Filament\Pages\SettingsPage;
use Filament\Forms\Components\Toggle;

class ManageLogin extends SettingsPage
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user';

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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    Toggle::make('whitelist_active')
                        ->label(__('settings.activate_whitelist'))
                ])
            ]);
    }
}

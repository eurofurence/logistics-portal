<?php

namespace App\Filament\Admin\Pages;

use App\Settings\LoginSettings;
use Filament\Facades\Filament;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManageLogin extends SettingsPage
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user';

    protected static string $settings = LoginSettings::class;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        $panel = Filament::getCurrentPanel();

        return $user !== null
            && $panel?->getId() === 'admin'
            && $user->canAccessPanel($panel)
            && $user->can('access-login-settings');
    }

    public function canEdit(): bool
    {
        return static::canAccess();
    }

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
                        ->label(__('settings.activate_whitelist')),
                    Toggle::make('two_factor_enabled')
                        ->label(__('settings.two_factor_enabled'))
                        ->helperText(__('settings.two_factor_help')),
                    Toggle::make('passkeys_enabled')
                        ->label(__('settings.passkeys_enabled'))
                        ->helperText(__('settings.passkeys_help')),
                ]),
            ]);
    }
}

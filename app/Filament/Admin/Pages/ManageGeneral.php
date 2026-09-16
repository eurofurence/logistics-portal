<?php

namespace App\Filament\Admin\Pages;

use App\Settings\GeneralSettings;
use DateTimeZone;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManageGeneral extends SettingsPage
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $settings = GeneralSettings::class;

    public static function getNavigationGroup(): ?string
    {
        return __('general.settings');
    }

    public function getTitle(): string
    {
        return __('settings.general');
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.general');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessPanel(Filament::getPanel('admin')) ?? false;
    }

    public function canEdit(): bool
    {
        return static::canAccess();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make([
                Select::make('timezone')
                    ->label(__('settings.timezone'))
                    ->helperText(__('settings.timezone_help'))
                    ->options(array_combine(DateTimeZone::listIdentifiers(), DateTimeZone::listIdentifiers()))
                    ->searchable()
                    ->rules(['timezone'])
                    ->required(),
            ]),
        ]);
    }
}

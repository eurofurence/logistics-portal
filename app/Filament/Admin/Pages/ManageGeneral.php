<?php

namespace App\Filament\Admin\Pages;

use App\Settings\GeneralSettings;
use DateTimeZone;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                TextInput::make('site_name')
                    ->label(__('settings.site_name'))
                    ->placeholder(config('app.name'))
                    ->maxLength(120),
                Textarea::make('site_description')
                    ->label(__('settings.site_description'))
                    ->helperText(__('settings.site_description_help'))
                    ->rows(3)
                    ->maxLength(300),
                Select::make('timezone')
                    ->label(__('settings.timezone'))
                    ->helperText(__('settings.timezone_help'))
                    ->options(array_combine(DateTimeZone::listIdentifiers(), DateTimeZone::listIdentifiers()))
                    ->searchable()
                    ->rules(['timezone'])
                    ->required(),
                Select::make('default_locale')
                    ->label(__('settings.default_locale'))
                    ->helperText(__('settings.default_locale_help'))
                    ->options(config('app.available_locales'))
                    ->required(),
            ]),
            Section::make(__('settings.seo'))
                ->schema([
                    TextInput::make('seo_keywords')
                        ->label(__('settings.seo_keywords'))
                        ->helperText(__('settings.seo_keywords_help'))
                        ->maxLength(500),
                    Toggle::make('search_engine_indexing')
                        ->label(__('settings.search_engine_indexing'))
                        ->helperText(__('settings.search_engine_indexing_help')),
                ]),
        ]);
    }
}

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
        $user = auth()->user();
        $panel = Filament::getCurrentPanel();

        return $user !== null
            && $panel?->getId() === 'admin'
            && $user->canAccessPanel($panel)
            && $user->can('access-general-settings');
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
                TextInput::make('dashboard_url')
                    ->label(__('settings.dashboard_url'))
                    ->helperText(__('settings.dashboard_url_help'))
                    ->url()
                    ->rules(['url:http,https'])
                    ->maxLength(2048)
                    ->required(),
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
            Section::make(__('homepage.settings_title'))
                ->description(__('homepage.settings_help'))
                ->schema([
                    TextInput::make('homepage_title')->label(__('homepage.title_label'))->maxLength(120),
                    Textarea::make('homepage_intro')->label(__('homepage.intro_label'))->rows(3)->maxLength(600),
                    ...collect(['overview', 'guide'])->map(fn (string $tab): Section => Section::make(__('homepage.'.$tab))
                        ->schema([
                            Toggle::make('homepage_'.$tab.'_enabled')->label(__('homepage.tab_enabled'))->rules(['boolean']),
                            ...collect(['orders', 'bills', 'wishlists', 'inventory'])->map(fn (string $area): Toggle => Toggle::make('homepage_'.$tab.'_'.$area)
                                ->label(__('homepage.'.$area))->rules(['boolean']))->all(),
                        ])->columns(2))->all(),
                    ...collect(['orders', 'bills', 'wishlists', 'inventory'])->map(fn (string $area): Textarea => Textarea::make('homepage_'.$area.'_description')
                        ->label(__('homepage.description_label', ['area' => __('homepage.'.$area)]))
                        ->placeholder(__('homepage.'.$area.'_description'))->rows(3)->maxLength(400))->all(),
                ])->columnSpanFull(),
        ]);
    }
}

<?php

namespace App\Filament\App\Resources\OrderArticles\Schemas;

use App\Models\OrderCategory;
use App\Services\AsinDataService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;

class OrderArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('form_tabs_1')
                    ->tabs([
                        Tab::make('info_tab')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->label(__('general.name'))
                                    ->maxLength(255)
                                    ->live(true),
                                Textarea::make('description')
                                    ->nullable()
                                    ->maxLength(1000)
                                    ->label(__('general.description')),
                                Select::make('category')
                                    ->label(__('general.category'))
                                    ->options(OrderCategory::query()->pluck('name', 'id'))
                                    ->searchable()
                                    ->exists('order_categories', 'id'),
                                Fieldset::make(__('general.price'))
                                    ->schema([
                                        TextInput::make('price_net')
                                            ->required()
                                            ->suffixIcon('heroicon-o-currency-euro')
                                            ->label(__('general.price_net'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(config('constants.inputs.numeric.max'))
                                            ->step(0.01)
                                            ->placeholder(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if ($get('auto_calculate')) {
                                                    $taxRate = $get('tax_rate');
                                                    $priceGross = $state * (1 + $taxRate / 100);
                                                    $set('price_gross', round($priceGross, 2));
                                                }
                                            }),
                                        TextInput::make('price_gross')
                                            ->required()
                                            ->suffixIcon('heroicon-o-currency-euro')
                                            ->label(__('general.price_gross'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(config('constants.inputs.numeric.max'))
                                            ->step(0.01)
                                            ->placeholder(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if ($get('auto_calculate')) {
                                                    $taxRate = $get('tax_rate');
                                                    $priceNet = $state / (1 + $taxRate / 100);
                                                    $set('price_net', round($priceNet, 2));
                                                }
                                            }),
                                        TextInput::make('tax_rate')
                                            ->required()
                                            ->suffix('%')
                                            ->label(__('general.tax_rate'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->step(0.01)
                                            ->default(19)
                                            ->maxValue(10000)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if ($get('auto_calculate')) {
                                                    $priceNet = $get('price_net');
                                                    $priceGross = $priceNet * (1 + ($state / 100));
                                                    $set('price_gross', round($priceGross, 2));
                                                }
                                            }),
                                        TextInput::make('returning_deposit')
                                            ->required()
                                            ->suffixIcon('heroicon-o-currency-euro')
                                            ->label(__('general.returning_deposit'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(config('constants.inputs.numeric.max'))
                                            ->hint(__('general.returning_deposit_is_gross'))
                                            ->step(0.01)
                                            ->default(0),
                                        Section::make(__('general.description'))
                                            ->schema([
                                                TextEntry::make('price_description')
                                                    ->state(__('general.price_calculation_description'))
                                                    ->columnSpanFull()
                                                    ->hiddenLabel(true),
                                                Toggle::make('auto_calculate')
                                                    ->label(__('general.auto_calculate'))
                                                    ->default(1),
                                            ])
                                            ->collapsed(),
                                    ]),
                                TextInput::make('picture')
                                    ->placeholder('http://example.com/picture.png')
                                    ->nullable()
                                    ->url()
                                    ->maxLength(5000)
                                    ->label(__('general.picture')),
                                TextInput::make('url')
                                    ->nullable()
                                    ->maxLength(10000)
                                    ->required()
                                    ->label(__('general.url_to_product'))
                                    ->suffixActions([
                                        Action::make('getProductData')
                                            ->icon('heroicon-m-arrow-path')
                                            ->color('info')
                                            ->requiresConfirmation()
                                            ->schema([
                                                TextEntry::make(__('general.hint'))
                                                    ->state(new HtmlString('<b><label style="color: orange">'.__('general.selected_fields_will_be_overwritten').'</label></b>'))
                                                    ->extraAttributes(['class' => 'text-red-500'])
                                                    ->columnSpanFull(),
                                                Select::make('fields')
                                                    ->label(__('general.select_fields'))
                                                    ->options([
                                                        'name' => __('general.name'),
                                                        // 'description' => __('general.description'),
                                                        'price_gross' => __('general.price_gross'),
                                                        'picture' => __('general.picture'),
                                                        'url' => __('general.url'),
                                                        'article_number' => __('general.article_number'),
                                                    ])
                                                    ->default([
                                                        'price_gross',
                                                        'picture',
                                                        'url',
                                                        'article_number',
                                                    ])
                                                    ->multiple()
                                                    ->reactive()
                                                    ->required(),
                                                Toggle::make('select_all')
                                                    ->label(__('general.select_all'))
                                                    ->reactive()
                                                    ->afterStateUpdated(function (Set $set, $state) {
                                                        if ($state) {
                                                            $set('fields', [
                                                                'name',
                                                                // 'description',
                                                                'price_gross',
                                                                'picture',
                                                                'url',
                                                                'article_number',
                                                            ]);
                                                        } else {
                                                            $set('fields', []);
                                                        }
                                                    })->default(false),
                                            ])
                                            ->action(function (Get $get, Set $set, array $data) {
                                                $url = $get('url');

                                                if (empty($url)) {
                                                    return;
                                                }

                                                if (preg_match('/https?:\/\/(www\.)?amazon\.[a-z]{2,3}(\/.*)?$/', $url)) {
                                                    $asin_data_service = new AsinDataService;

                                                    // Checking if a job is already in progress
                                                    $isJobRunning = Cache::get('SyncDataToOrderArticleJob_running', false);

                                                    if ($isJobRunning) {
                                                        Notification::make()
                                                            ->info()
                                                            ->title(__('general.job_already_running'))
                                                            ->body(__('general.job_is_currently_running'))
                                                            ->send();

                                                        return;
                                                    }

                                                    if ($asin_data_service->getCredits() <= 0) {
                                                        Notification::make()
                                                            ->warning()
                                                            ->title(__('general.not_enough_credits'))
                                                            ->body(__('general.please_inform_an_admin'))
                                                            ->send();

                                                        return;
                                                    }

                                                    if (! empty($data['fields'])) {
                                                        $asin = $asin_data_service->extractASIN($url);
                                                        $product_data = $asin_data_service->getProductData($asin);

                                                        if (in_array('name', $data['fields'])) {
                                                            $set('name', $product_data['product']['title']);
                                                        }

                                                        /*
                                                if ($data['fields'] == 'description') {
                                                    $set('name', $product_data['product']['description']);
                                                }
                                                */

                                                        if (in_array('price_gross', $data['fields'])) {
                                                            $set('price_gross', $product_data['product']['buybox_winner']['price']['value']);
                                                            if ($get('auto_calculate')) {
                                                                $taxRate = $get('tax_rate');
                                                                $priceNet = $product_data['product']['buybox_winner']['price']['value'] / (1 + $taxRate / 100);
                                                                $set('price_net', round($priceNet, 2));
                                                            }
                                                        }

                                                        if (in_array('picture', $data['fields'])) {
                                                            $set('picture', $product_data['product']['main_image']['link']);
                                                        }

                                                        if (in_array('url', $data['fields'])) {
                                                            $set('url', $product_data['product']['link']);
                                                        }

                                                        if (in_array('article_number', $data['fields'])) {
                                                            $set('article_number', $product_data['product']['asin']);
                                                        }

                                                        Notification::make()
                                                            ->success()
                                                            ->title(__('general.fields_updated'))
                                                            ->body(__('general.fields_updated_successfully'))
                                                            ->send();
                                                    }

                                                    return;
                                                }

                                                Notification::make()
                                                    ->info()
                                                    ->title(__('general.no_valid_link'))
                                                    ->send();
                                            }),

                                    ]),
                                TextInput::make('article_number')
                                    ->nullable()
                                    ->maxLength(255)
                                    ->label(__('general.article_number'))
                                    ->reactive(),
                                Textarea::make('comment')
                                    ->nullable()
                                    ->maxLength(10000)
                                    ->label(__('general.comment')),
                            ])
                            ->label(__('general.informations'))
                            ->icon('heroicon-o-list-bullet'),
                        Tab::make('options')
                            ->schema([
                                Fieldset::make('lock_article_set')
                                    ->schema([
                                        Toggle::make('locked')
                                            ->columns(1)
                                            ->label(__('general.is_active'))
                                            ->inline(false)
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                $set('locked_reason_visible', $state);
                                            }),
                                        TextInput::make('locked_reason')
                                            ->label(__('general.reason'))
                                            ->visible(fn (Get $get) => $get('locked') === true),
                                    ])
                                    ->label(__('general.lock'))
                                    ->columns(2),
                                Fieldset::make('deadline_set')
                                    ->schema([
                                        DateTimePicker::make('deadline')
                                            ->label(__('general.date_and_time'))
                                            ->timezone('Europe/Berlin')
                                            ->seconds(false)
                                            ->nullable(),
                                    ])
                                    ->label(__('general.deadline'))
                                    ->columns(2),
                                Fieldset::make('important_note')
                                    ->schema([
                                        Textarea::make('important_note')
                                            ->label('')
                                            ->rows(3)
                                            ->nullable()
                                            ->maxLength(1024)
                                            ->autosize(),
                                    ])
                                    ->label(__('general.important_note'))
                                    ->columns(2),
                            ])
                            ->label(__('general.options'))
                            ->icon('heroicon-o-adjustments-horizontal'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}

<?php

namespace App\Filament\App\Resources;

use DateTime;
use DateTimeZone;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\OrderArticle;
use App\Models\OrderCategory;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use App\Actions\TableOrderAction;
use App\Services\AsinDataService;
use Filament\Infolists\Components;
use Filament\Support\Colors\Color;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Tabs;
use Illuminate\Support\Facades\Bus;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Cache;
use Filament\Forms\Components\Section;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\FontWeight;
use App\Jobs\SyncDataToOrderArticleJob;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Actions\RestoreAction;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Collection;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\Action as TableAction;
use App\Filament\App\Resources\OrderArticleResource\Pages;
use Filament\Forms\Components\Actions\Action as FormAction;
use Njxqlus\Filament\Components\Infolists\LightboxImageEntry;

class OrderArticleResource extends Resource
{

    protected static ?string $model = OrderArticle::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.orders');

        return static::$navigationGroup;
    }

    public static function getNavigationLabel(): string
    {
        return __('general.article_directory');
    }

    public static function getModelLabel(): string
    {
        return __('general.article');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.articles');
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('general.price') => $record->price_gross . '€',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('form_tabs_1')
                    ->tabs([
                        Tabs\Tab::make('info_tab')
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
                                    ->options(OrderCategory::all()->pluck('name', 'id'))
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
                                                Placeholder::make('price_description')
                                                    ->content(__('general.price_calculation_description'))
                                                    ->columnSpanFull()
                                                    ->hiddenLabel(true),
                                                Toggle::make('auto_calculate')
                                                    ->label(__('general.auto_calculate'))
                                                    ->default(1),
                                            ])
                                            ->collapsed()
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
                                        FormAction::make('getProductData')
                                            ->icon('heroicon-m-arrow-path')
                                            ->color('info')
                                            ->requiresConfirmation()
                                            ->form([
                                                Placeholder::make(__('general.hint'))
                                                    ->content(new HtmlString('<b><label style="color: orange">' . __('general.selected_fields_will_be_overwritten') . '</label></b>'))
                                                    ->extraAttributes(['class' => 'text-red-500'])
                                                    ->columnSpanFull(),
                                                Select::make('fields')
                                                    ->label(__('general.select_fields'))
                                                    ->options([
                                                        'name' => __('general.name'),
                                                        //'description' => __('general.description'),
                                                        'price_gross' => __('general.price_gross'),
                                                        'picture' => __('general.picture'),
                                                        'url' => __('general.url'),
                                                        'article_number' => __('general.article_number'),
                                                    ])
                                                    ->default([
                                                        'price_gross',
                                                        'picture',
                                                        'url',
                                                        'article_number'
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
                                                                //'description',
                                                                'price_gross',
                                                                'picture',
                                                                'url',
                                                                'article_number'
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
                                                    $asin_data_service = new AsinDataService();

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

                                                    if (!empty($data['fields'])) {
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
                                            })

                                    ]),
                                TextInput::make('article_number')
                                    ->nullable()
                                    ->maxLength(255)
                                    ->label(__('general.article_number'))
                                    ->reactive(),
                                TextArea::make('comment')
                                    ->nullable()
                                    ->maxLength(10000)
                                    ->label(__('general.comment')),
                            ])
                            ->label(__('general.informations'))
                            ->icon('heroicon-o-list-bullet'),
                        Tabs\Tab::make('options')
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
                                            ->visible(fn(Get $get) => $get('locked') === true),
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
                                    ->columns(2)
                            ])
                            ->label(__('general.options'))
                            ->icon('heroicon-o-adjustments-horizontal')
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Split::make([
                    ImageColumn::make('picture')
                        ->size(100)
                        ->grow(false),
                    Stack::make([
                        TextColumn::make('name')
                            ->weight(FontWeight::Bold)
                            ->searchable()
                            ->sortable(),
                        TextColumn::make('description')
                            ->color('gray')
                            ->limit(100),
                        TextColumn::make('price_net')
                            ->label(__('general.price_net'))
                            ->sortable()
                            ->formatStateUsing(function (Model $record) {
                                $priceNet = number_format($record->price_net, 2);
                                $returningDeposit = number_format($record->returning_deposit, 2);
                                $currencySymbol = match ($record->currency) {
                                    'EUR' => '€',
                                    'USD' => '$',
                                    default => '€',
                                };

                                if ($record->returning_deposit > 0) {
                                    return "{$priceNet}{$currencySymbol} <span style='color: gray; font-size: 0.9em;'> + (" . __('general.returning_deposit') . ": {$returningDeposit}{$currencySymbol})</span>";
                                }

                                return "{$priceNet}{$currencySymbol}";
                            })
                            ->html(),
                        TextColumn::make('article_number')
                            ->searchable()
                            ->html()
                            ->formatStateUsing(function () {
                                return "";
                            }),
                    ])
                ])
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->visible(fn(OrderArticle $record): bool => Gate::allows('restore', $record) || Gate::allows('forceDelete', $record) || Gate::allows('bulkForceDelete', $record) || Gate::allows('bulkRestore', $record)),
                SelectFilter::make('category')
                    ->label(__('general.category'))
                    ->options(OrderCategory::all()->pluck('name', 'id'))
                    ->searchable(),
                SelectFilter::make('url')
                    ->label(__('general.marketplace'))
                    ->options([
                        'frog_store' => __('general.frog_store'),
                        'metro' => __('general.metro'),
                        'amazon' => __('general.amazon'),
                        'hornbach' => __('general.hornbach'),
                    ])
                    ->multiple()
                    ->query(function (Builder $query, $data): Builder {
                        if (!empty($data['values'])) {
                            $query->where(function ($query) use ($data) {
                                foreach ($data['values'] as $value) {
                                    if ($value === 'frog_store') {
                                        $query->orWhere('url', 'like', '%frog_store.%');
                                    }

                                    if ($value === 'metro') {
                                        $query->orWhere('url', 'like', '%metro.%');
                                    }

                                    if ($value === 'amazon') {
                                        $query->orWhere('url', 'like', '%amazon.%')->orWhere('url', 'like', '%amzn.%');
                                    }

                                    if ($value === 'hornbach') {
                                        $query->orWhere('url', 'like', '%hornbach.%');
                                    }
                                }
                            });
                        }

                        return $query;
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->paginated([
                18,
                36,
                72,
                'all',
            ])
            ->actions([
                TableOrderAction::make()
                    ->button()
                    ->size(ActionSize::ExtraSmall),
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()
                        ->modalHeading(function ($record): string {
                            return __('general.delete') . ': ' . $record->name;
                        }),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ])
                    ->button()
                    ->size(ActionSize::ExtraSmall)
                    ->color(Color::Indigo)
                    ->outlined(),
                TableAction::make('article_note')
                    ->label(__('general.note'))
                    ->icon('heroicon-o-shield-exclamation')
                    ->color(Color::Yellow)
                    ->size(ActionSize::ExtraSmall)
                    ->visible(function (Model $record): bool {
                        return !empty(static::getOrderArticleNotes($record));
                    })
                    ->form(function (Model $record) {
                        return [
                            ViewField::make('note_list')
                                ->view('components.form-list')
                                ->viewData([
                                    'notes' => static::getOrderArticleNotes($record)
                                ])
                        ];
                    })
                    ->modalIcon('heroicon-o-shield-exclamation')
                    ->modalWidth(MaxWidth::ExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(Gate::check('bulkDelete', OrderArticle::class)),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(Gate::check('bulkRestore', OrderArticle::class)),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(Gate::check('bulkForceDelete', OrderArticle::class)),
                    BulkAction::make('bulk_calc_gross_price')
                        ->label(__('general.recalculate_gross_price'))
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            foreach ($records as $article) {
                                if ($article->trashed()) {
                                    continue;
                                }

                                $priceGross = $article->price_net * (1 + $article->tax_rate / 100);

                                $article->update([
                                    'price_gross' => $priceGross
                                ]);
                            }

                            Notification::make()
                                ->body(__('general.successfully_recalculated'))
                                ->success()
                                ->icon('heroicon-o-check')
                                ->iconColor('success')
                                ->send();
                        })
                        ->visible(Auth::user()->can('can-use-article-directory-special-functions')),
                    BulkAction::make('SyncArticleDataToExternalSource')
                        ->color('info')
                        ->requiresConfirmation()
                        ->label(__('general.get_amazon_data'))
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->visible(Auth::user()->can('can-use-article-directory-special-functions'))
                        ->form([
                            Placeholder::make(__('general.hint'))
                                ->content(new HtmlString('<b><label style="color: orange">' . __('general.selected_fields_will_be_overwritten') . '</label></b>'))
                                ->extraAttributes(['class' => 'text-red-500'])
                                ->columnSpanFull(),
                            Select::make('fields')
                                ->label(__('general.select_fields'))
                                ->options([
                                    'name' => __('general.name'),
                                    //'description' => __('general.description'),
                                    'price_gross' => __('general.price_gross'),
                                    'picture' => __('general.picture'),
                                    'url' => __('general.url'),
                                    'article_number' => __('general.article_number'),
                                ])
                                ->default([
                                    'price_gross',
                                    'url',
                                    'article_number'
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
                                            //'description',
                                            'price_gross',
                                            'picture',
                                            'url',
                                            'article_number'
                                        ]);
                                    } else {
                                        $set('fields', []);
                                    }
                                })->default(false),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $jobs = [];

                            foreach ($records as $article) {
                                if ($article->trashed()) {
                                    continue;
                                }

                                // Add the job to the array
                                $jobs[] = new SyncDataToOrderArticleJob($article, Auth::user()->id, $data['fields']);
                            }

                            // Now create the batch with the collected jobs
                            Bus::batch($jobs)
                                ->allowFailures()
                                ->dispatch();

                            Notification::make()
                                ->body(__('general.job_started'))
                                ->success()
                                ->icon('heroicon-o-check')
                                ->iconColor('success')
                                ->send();
                        }),
                    BulkAction::make('bulkChangeDeadline')
                        ->color(Color::Indigo)
                        ->requiresConfirmation()
                        ->label(__('general.set_deadline'))
                        ->icon('heroicon-o-calendar-days')
                        ->visible(Gate::allows('bulkEditDeadline'))
                        ->form([
                            DateTimePicker::make('deadline')
                                ->label(__('general.date_and_time'))
                                ->timezone('Europe/Berlin')
                                ->seconds(false)
                                ->nullable(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            foreach ($records as $article) {
                                if ($article->trashed()) {
                                    continue;
                                }

                                $article->update([
                                    'deadline' => $data['deadline']
                                ]);
                            }

                            Notification::make()
                                ->body(__('general.deadline_set'))
                                ->success()
                                ->icon('heroicon-o-check')
                                ->iconColor('success')
                                ->send();
                        })
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make()
                    ->schema([
                        TextEntry::make('special_notes')
                            ->label('')
                            ->default(function (Model $record) {
                                return static::getOrderArticleNotes($record);
                            })
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->limitList(3)
                            ->expandableLimitedList()
                    ])
                    ->description(__('general.note'))
                    ->icon('heroicon-m-shield-exclamation')
                    ->iconColor('warning')
                    ->visible(function (Model $record) {
                        return $record->locked || !empty($record->deadline);
                    }),
                Components\Section::make(__('general.informations'))
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        LightboxImageEntry::make('picture')
                            ->href(
                                function (Model $record): string {
                                    return $record->picture;
                                }
                            )
                            ->extraImgAttributes([
                                'style' => 'object-fit: contain',
                            ], true)
                            ->label(__('general.picture'))
                            ->visible(function (Model $record) {
                                return $record->picture;
                            })
                            ->size(255)
                            ->slideHeight('100%')
                            ->slideWidth('100%'),
                        Components\Group::make([
                            TextEntry::make('name')
                                ->label(__('general.name')),
                            TextEntry::make('price_net')
                                ->money(fn(Model $record) => match ($record->currency) {
                                    'EUR' => 'EUR',
                                    'USD' => 'USD',
                                    default => 'EUR',
                                })
                                ->label(__('general.price_net')),
                            TextEntry::make('price_gross')
                                ->money(fn(Model $record) => match ($record->currency) {
                                    'EUR' => 'EUR',
                                    'USD' => 'USD',
                                    default => 'EUR',
                                })
                                ->label(__('general.price_gross')),
                            TextEntry::make('tax_rate')
                                ->label(__('general.tax_rate'))
                                ->suffix('%'),
                            TextEntry::make('article_number')
                                ->label(__('general.article_number'))
                                ->default(__('general.not_set'))
                                ->visible(function (Model $record) {
                                    return $record->article_number;
                                })
                        ]),
                        Components\Group::make([
                            TextEntry::make('returning_deposit')
                                ->money(fn(Model $record) => match ($record->returning_deposit) {
                                    'EUR' => 'EUR',
                                    'USD' => 'USD',
                                    default => 'EUR',
                                })
                                ->label(__('general.returning_deposit'))
                                ->hint(__('general.additional') . ', ' . __('general.gross'))
                                ->visible(fn($record) => $record->returning_deposit > 0),
                            TextEntry::make('url')
                                ->url(function (Model $record) {
                                    return $record->url;
                                }, true)
                                ->default(__('general.not_set'))
                                ->limit(45)
                                ->visible(function (Model $record) {
                                    return $record->url;
                                }),
                            TextEntry::make('categorie.name')
                                ->label(__('general.category'))
                                ->default(__('general.not_set'))
                                ->url(function (Model $record) {
                                    if (!empty($record->categorie)) {
                                        return route('filament.app.resources.order-articles.index') . '?tableFilters[category][value]=' . $record->categorie->id;
                                    }
                                }, true),
                            TextEntry::make('description')
                                ->label(__('general.description'))
                                ->visible(function (Model $record) {
                                    return $record->description;
                                }),
                        ])
                    ]),
                Components\Section::make(__('general.comment'))
                    ->schema([
                        TextEntry::make('comment')
                            ->default(__('general.not_set'))
                            ->label('')
                    ])
                    ->visible(function (Model $record) {
                        return $record->comment;
                    }),
                Components\Section::make(__('general.other_infos'))
                    ->schema([
                        Components\Split::make([
                            Components\Group::make([
                                TextEntry::make('added_by.name')
                                    ->label(__('general.added_by')),
                                TextEntry::make('edited_by.name')
                                    ->label(__('general.edited_by'))
                            ]),
                            Components\Group::make([
                                TextEntry::make('created_at')
                                    ->label(__('general.created_at'))
                                    ->dateTime(timezone: 'Europe/Berlin'),
                                TextEntry::make('updated_at')
                                    ->label(__('general.updated_at'))
                                    ->dateTime(timezone: 'Europe/Berlin'),
                            ])
                        ])
                    ])
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrderArticles::route('/'),
            'create' => Pages\CreateOrderArticle::route('/create'),
            'edit' => Pages\EditOrderArticle::route('/{record}/edit'),
            'view' => Pages\ViewOrderArticle::route('{record}')
        ];
    }

    /**
     * The function `getOrderArticleNotes` returns an array of notes related to a given order article, including
     * information about locking status and deadline.
     *
     * @param Model record The `getOrderArticleNotes` function takes a `Model` object as a parameter named `record`. It
     * checks if the record is locked and if there is a deadline set for the order article. If the record is locked, it
     * adds a note to the output array mentioning the reason for locking.
     *
     * @return array An array of notes related to the order article, including information about whether the article is
     * locked and the reason for it being locked, as well as the order deadline if it is set.
     */
    public static function getOrderArticleNotes(Model $record): array
    {
        $output = array();

        if ($record->locked) {
            $output[] = __('general.this_article_is_locked_because') . ': ' . $record->locked_reason;
        }

        if (!empty($record->deadline)) {
            $deadline = new DateTime($record->deadline, new DateTimeZone('UTC'));

            // Converting to the Berlin time zone
            $deadline->setTimezone(new DateTimeZone('Europe/Berlin'));
            $formattedDeadline = $deadline->format('Y-m-d H:i:s');

            $output[] = __('general.order_deadline') . ': ' . $formattedDeadline;
        }

        if (!empty($record->important_note)) {
            $output[] = __('general.important_note') . ': ' . $record->important_note;
        }

        return $output;
    }
}

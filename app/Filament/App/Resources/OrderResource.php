<?php

namespace App\Filament\App\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Order;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use App\Models\Department;
use App\Models\OrderEvent;
use Filament\Tables\Table;
use App\Exports\MetroExport;
use App\Models\OrderArticle;
use App\Models\OrderRequest;
use Illuminate\Support\Carbon;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Tables\Grouping\Group;
use Illuminate\Support\Facades\Log;
use App\Exports\OrderStandardExport;
use Filament\Forms\Components\Radio;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Wizard\Step;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Forms\Components\CheckboxList;
use Filament\Tables\Columns\TextInputColumn;
use Illuminate\Database\Eloquent\Collection;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Actions\Action as TableAction;
use App\Filament\App\Resources\OrderResource\Pages;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static $export_column_options = array();

    protected function getTableQuery()
    {
        return parent::getTableQuery()
            ->with([
                'event',
                'department',
            ]);
    }

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.orders');

        return static::$navigationGroup;
    }

    public static function getNavigationLabel(): string
    {
        return __('general.orders');
    }

    public static function getModelLabel(): string
    {
        return __('general.order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.orders');
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
            __('general.department') => $record->department->name,
            __('general.order_event') => $record->event->name
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('tabs')
                    ->tabs([
                        Tabs\Tab::make(__('general.general'))
                            ->icon('heroicon-m-bars-3')
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 2,
                                    'lg' => 2,
                                ])
                                    ->schema([
                                        TextInput::make('name')
                                            ->label(__('general.name'))
                                            ->required()
                                            ->maxLength(255),
                                        Select::make('department_id')
                                            ->label(__('general.department'))
                                            ->required()
                                            ->exists('departments', 'id')
                                            ->options(function (): array {
                                                $options = Auth::user()->can('access-all-departments')
                                                    ? Department::withoutTrashed()->pluck('name', 'id')->toArray()
                                                    : Auth::user()->departments()->withoutTrashed()->pluck('name', 'department_id')->toArray();

                                                return $options;
                                            })
                                            ->default(function () {
                                                $options = Auth::user()->can('access-all-departments')
                                                    ? Department::withoutTrashed()->pluck('id')->toArray()
                                                    : Auth::user()->departments()->withoutTrashed()->pluck('department_id')->toArray();

                                                return count($options) === 1 ? $options[0] : null;
                                            }),
                                        Select::make('order_event_id')
                                            ->label(__('general.order_event'))
                                            ->exists('order_events', 'id')
                                            ->required()
                                            ->options(function (): array {
                                                $options = Auth::user()->can('can-always-order')
                                                    ? OrderEvent::withoutTrashed()->pluck('name', 'id')->toArray()
                                                    : OrderEvent::where('locked', false)
                                                    ->where(function ($query) {
                                                        $query->whereNull('order_deadline')
                                                            ->orWhere('order_deadline', '>', now());
                                                    })
                                                    ->withoutTrashed()
                                                    ->pluck('name', 'id')
                                                    ->toArray();

                                                return $options;
                                            })
                                            ->default(function () {
                                                $options = Auth::user()->can('can-always-order')
                                                    ? OrderEvent::withoutTrashed()->pluck('id')->toArray()
                                                    : OrderEvent::where('locked', false)
                                                    ->where(function ($query) {
                                                        $query->whereNull('order_deadline')
                                                            ->orWhere('order_deadline', '>', now());
                                                    })
                                                    ->withoutTrashed()
                                                    ->pluck('id')
                                                    ->toArray();

                                                return count($options) === 1 ? $options[0] : null;
                                            }),
                                        Textarea::make('description')
                                            ->label(__('general.description'))
                                            ->maxLength(10000)
                                            ->columnSpanFull(),
                                        Fieldset::make('price_and_amount')
                                            ->schema([
                                                TextInput::make('amount')
                                                    ->label(__('general.quantity'))
                                                    ->numeric()
                                                    ->default(1)
                                                    ->minValue(1)
                                                    ->maxValue(1000000)
                                                    ->required(),
                                                TextInput::make('price_net')
                                                    ->label(__('general.price_net'))
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(0)
                                                    ->step(0.01)
                                                    ->maxValue(config('constants.inputs.numeric.max'))
                                                    ->hint(__('general.per_item'))
                                                    ->default(0)
                                                    ->live(debounce: 1000)
                                                    ->suffixIcon('heroicon-m-currency-euro')
                                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                        if ($get('auto_calculate')) {
                                                            $taxRate = $get('tax_rate');
                                                            $priceGross = $state * (1 + $taxRate / 100);
                                                            $set('price_gross', round($priceGross, 2));
                                                        }
                                                    }),
                                                TextInput::make('price_gross')
                                                    ->label(__('general.price_gross'))
                                                    ->required()
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->step(0.01)
                                                    ->maxValue(config('constants.inputs.numeric.max'))
                                                    ->hint(__('general.per_item'))
                                                    ->default(0)
                                                    ->live(debounce: 1000)
                                                    ->suffixIcon('heroicon-m-currency-euro')
                                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                        if ($get('auto_calculate')) {
                                                            $taxRate = $get('tax_rate');
                                                            $priceNet = $state / (1 + ($taxRate / 100));
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
                                                    ->live(debounce: 1000)
                                                    ->maxValue(config('constants.inputs.numeric.max'))
                                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                        if ($get('auto_calculate')) {
                                                            $priceNet = $get('price_net');
                                                            $priceGross = $priceNet * (1 + ($state / 100));
                                                            $set('price_gross', round($priceGross, 2));
                                                        }
                                                    }),
                                                TextInput::make('payment_method')
                                                    ->label(__('general.payment_method'))
                                                    ->maxLength(100),
                                                TextInput::make('returning_deposit')
                                                    ->label(__('general.returning_deposit'))
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->step(0.01)
                                                    ->maxValue(config('constants.inputs.numeric.max'))
                                                    ->hint(__('general.per_item') . ', ' . __('general.gross'))
                                                    ->default(0)
                                                    ->required(),
                                                TextInput::make('discount_net')
                                                    ->label(__('general.discount_net'))
                                                    ->numeric()
                                                    ->nullable()
                                                    ->minValue(0)
                                                    ->maxValue(function (Get $get): float {
                                                        if ($get('price_net') == 0) {
                                                            return 0;
                                                        }

                                                        (float)$max_discount = $get('price_net') * $get('amount');

                                                        if ($max_discount > config('constants.inputs.numeric.max')) {
                                                            return config('constants.inputs.numeric.max');
                                                        }

                                                        return $max_discount;
                                                    })
                                                    ->step(0.01)
                                                    ->hint(__('general.whole_order')),
                                                Section::make(__('general.description'))
                                                    ->schema([
                                                        Placeholder::make('price_description')
                                                            ->content(__('general.price_calculation_description'))
                                                            ->columnSpanFull()
                                                            ->hiddenLabel(true),
                                                        Toggle::make('auto_calculate')
                                                            ->label(__('general.auto_calculate'))
                                                            ->default(true),
                                                    ])
                                                    ->collapsed()
                                            ])
                                            ->label(__('general.price_and_amount'))
                                            ->columns([
                                                'default' => 1,
                                                'sm' => 1,
                                                'md' => 2,
                                                'lg' => 3,
                                            ]),
                                        Fieldset::make(__('general.miscellaneous'))
                                            ->schema([
                                                Textarea::make('contact')
                                                    ->label(__('general.contact'))
                                                    ->maxLength(10000)
                                                    ->columnSpan(2),
                                                TextInput::make('url')
                                                    ->label(__('general.url'))
                                                    ->required(true)
                                                    ->url()
                                                    ->minLength(4)
                                                    ->hint(__('general.url'))
                                                    ->suffixIcon('heroicon-m-globe-alt')
                                                    ->maxLength(100000)
                                                    ->columnSpan(2),
                                                TextInput::make('picture')
                                                    ->label(__('general.picture'))
                                                    ->url()
                                                    ->minLength(4)
                                                    ->maxLength(100000)
                                                    ->hint(__('general.url'))
                                                    ->suffixIcon('heroicon-m-globe-alt')
                                                    ->columnSpan(2),
                                                TextInput::make('article_number')
                                                    ->label(__('general.article_number'))
                                                    ->maxLength(500)
                                                    ->columnSpan(1),
                                                TextInput::make('order_number')
                                                    ->label(__('general.order_number'))
                                                    ->maxLength(250)
                                                    ->columnSpan(1),
                                                Textarea::make('comment')
                                                    ->label(__('general.comment'))
                                                    ->maxLength(100000)
                                                    ->columnSpan(2),
                                            ])
                                    ]),
                            ]),
                        Tabs\Tab::make(__('general.status'))
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Select::make('status')
                                    ->options([
                                        'on_hold' => __('general.on_hold'),
                                        'checking' => __('general.checking'),
                                        'processing' => __('general.processing'),
                                        'open' => __('general.open'),
                                        'ordered' => __('general.ordered'),
                                        'delivered' => __('general.delivered'),
                                        'partially_received' => __('general.partially_received'),
                                        'received' => __('general.received'),
                                        'rejected' => __('general.rejected'),
                                        'locked' => __('general.locked'),
                                        'refunded' => __('general.refunded')
                                    ])
                                    ->default('open'),
                            ])->visible(Auth::user()->can('can-change-order-status')),
                        Tabs\Tab::make(__('general.files'))
                            ->icon('heroicon-o-document')
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('files')
                                    ->collection('orders')
                                    ->directory('orders/files')
                                    ->multiple()
                                    ->panelLayout('list')
                                    ->appendFiles()
                                    ->openable()
                                    ->maxSize(50000)
                                    ->downloadable()
                                    ->visibility('private')
                                    ->disabled(!Auth::user()->can('can-edit-order-files')),
                            ])->visible(Auth::user()->can('can-see-order-files-tab')),
                        Tabs\Tab::make(__('general.more'))
                            ->icon('heroicon-o-ellipsis-horizontal-circle')
                            ->schema([
                                Fieldset::make('delivery')
                                    ->schema([
                                        TextInput::make('delivery_provider')
                                            ->label(__('general.delivery_provider'))
                                            ->maxLength(250)
                                            ->placeholder(__('general.amazon'))
                                            ->datalist([
                                                'Amazon',
                                                'Frog Store',
                                                'Metro',
                                                'Hornbach',
                                                'MediaMarkt',
                                                'Saturn',
                                                'Edeka',
                                                'OBI',
                                                'Toom Baumarkt',
                                                'Conrad',
                                                'IKEA',
                                                'Roller',
                                                'Poco',
                                                'Möbel Höffner',
                                                'Mömax',
                                                'XXXLutz',
                                                'Segmüller',
                                                'Hagebau',
                                                'Bauhaus'
                                            ]),
                                        TextInput::make('delivery_by')
                                            ->label(__('general.delivery_by'))
                                            ->maxLength(250)
                                            ->placeholder(__('general.dhl'))
                                            ->datalist([
                                                'DHL',
                                                'Hermes',
                                                'DPD',
                                                'GLS',
                                                'UPS',
                                                'FedEx',
                                                'Deutsche Post',
                                                'GO! Express & Logistics',
                                                'TNT',
                                                'Trans-o-flex'
                                            ]),
                                        TextInput::make('delivery_costs')
                                            ->label(__('general.delivery_costs'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->step(0.01)
                                            ->maxValue(config('constants.inputs.numeric.max'))
                                            ->hint(__('general.gross'))
                                            ->default(0)
                                            ->required(false)
                                            ->suffixIcon('heroicon-m-currency-euro'),
                                        DateTimePicker::make('delivery_date')
                                            ->label(__('general.delivery_date'))
                                            ->seconds(false)
                                            ->timezone('Europe/Berlin')
                                            ->hint('Europe/Berlin'),
                                        Textarea::make('delivery_destination')
                                            ->label(__('general.delivery_destination'))
                                            ->maxLength(10000)
                                            ->autosize()
                                            ->visible(fn() => Auth::user()->can('can-view-order-delivery-address')),
                                        TextInput::make('tracking_number')
                                            ->label(__('general.tracking_number'))
                                            ->maxLength(254),
                                        DateTimePicker::make('ordered_at')
                                            ->label(__('general.ordered_at'))
                                            ->timezone('Europe/Berlin')
                                            ->seconds(false)
                                            ->hint('Europe/Berlin'),
                                        Toggle::make('instant_delivery')
                                            ->label(__('general.instant_delivery'))
                                            ->default(false)
                                            ->inline(false)
                                            ->helperText(__('general.delivery_needed_immediate'))
                                    ])
                                    ->label(__('general.delivery'))
                                    ->columns(2),
                                Fieldset::make('inventory')
                                    ->schema([
                                        TextInput::make('inv_id')
                                            ->label(__('general.inventory_id'))
                                            ->numeric()
                                            ->exists('items', 'id'),
                                        Toggle::make('booked_to_inventory')
                                            ->label(__('general.booked_to_inventory'))
                                            ->default(false)
                                            ->inline(false)
                                    ])
                                    ->label(__('general.inventory'))
                                    ->visible(Auth::user()->can('update-Item')),
                                Fieldset::make('special')
                                    ->schema([
                                        Toggle::make('dangerous_good')
                                            ->label(__('general.dangerous_good'))
                                            ->default(false)
                                            ->inline(false)
                                            ->helperText(__('general.dangerous_good_description')),
                                        Toggle::make('big_size')
                                            ->label(__('general.big_size'))
                                            ->default(false)
                                            ->inline(false),
                                        Toggle::make('needs_truck')
                                            ->label(__('general.needs_truck'))
                                            ->default(false)
                                            ->inline(false),
                                        Toggle::make('special_delivery')
                                            ->label(__('general.special_delivery'))
                                            ->default(false)
                                            ->inline(false),
                                        TextInput::make('special_flag_text')
                                            ->label(__('general.special_flag_text'))
                                            ->maxLength(250)
                                    ])
                                    ->label(__('general.special')),
                                Fieldset::make('')
                                    ->schema([
                                        Placeholder::make('added_by')
                                            ->label(__('general.added_by'))
                                            ->content(fn(Model $record) => $record->addedBy->name),
                                        Placeholder::make('edited_by')
                                            ->label(__('general.edited_by'))
                                            ->content(fn(Model $record) => $record->editedBy->name),
                                        Placeholder::make('created_at')
                                            ->label(__('general.created_at'))
                                            ->content(fn(Model $record) => Carbon::parse($record->created_at)->timezone('Europe/Berlin')),
                                        Placeholder::make('updated_at')
                                            ->label(__('general.updated_at'))
                                            ->content(fn(Model $record) => Carbon::parse($record->updated_at)->timezone('Europe/Berlin')),
                                    ])
                                    ->hiddenOn(Pages\CreateOrder::class)
                            ]),
                        Tabs\Tab::make(__('general.relationships'))
                            ->icon('heroicon-o-link')
                            ->schema([
                                Select::make('order_article_id')
                                    ->relationship('directoryArticle', 'name', fn(Builder $query) => $query->withTrashed())
                                    ->label(__('general.article_directory'))
                                    ->searchable(['id', 'name'])
                                    ->hint(__('general.search_for_name_or_id')),
                                Select::make('order_request_id')
                                    ->relationship('orderRequest', 'title', fn(Builder $query) => $query->withTrashed())
                                    ->label(__('general.order_request'))
                                    ->searchable(['id', 'title'])
                                    ->hint(__('general.search_for_name_or_id'))
                                    ->unique('orders', 'order_request_id'),
                            ])
                            ->visible(Auth::user()->can('can-manage-order-relationships'))
                            ->disabled(!Auth::user()->can('can-manage-order-relationships')),
                    ])
                    ->columnSpanFull()
                    ->persistTab()
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        $query->when(!$user->can('access-all-departments') || !$user->can('can-see-all-departments'), function ($query) use ($user) {
            return $query->whereIn('department_id', $user->departments->pluck('id'));
        });

        return $query;
    }

    public static function table(Table $table): Table
    {
        $export_type_options = ['standart' => __('general.standart')];

        static::$export_column_options = [
            'id' => __('general.id'),
            'name' => __('general.name'),
            'description' => __('general.description'),
            'delivery_provider' => __('general.delivery_provider'),
            'delivery_by' => __('general.delivery_by'),
            'tracking_number' => __('general.tracking_number'),
            'delivery_date' => __('general.delivery_date'),
            'instant_delivery' => __('general.instant_delivery'),
            'amount' => __('general.amount'),
            'price_net' => __('general.price_net'),
            'price_gross' => __('general.price_gross'),
            'tax_rate' => __('general.tax_rate'),
            'payment_method' => __('general.payment_method'),
            'currency' => __('general.currency'),
            'url' => __('general.url'),
            'contact' => __('general.contact'),
            'dangerous_good' => __('general.dangerous_good'),
            'big_size' => __('general.big_size'),
            'needs_truck' => __('general.needs_truck'),
            'ordered_at' => __('general.ordered_at'),
            'comment' => __('general.comment'),
            'status' => __('general.status'),
            'created_at' => __('general.created_at'),
            'updated_at' => __('general.updated_at'),
            'user_note' => __('general.user_note'),
            'returning_deposit' => __('general.returning_deposit') . ' (' . __('general.single') . ')',
            'article_number' => __('general.article_number'),
        ];

        if (Auth::user()->can('can-use-special-order-export')) {
            $export_type_options['metro_list'] = __('general.metro_list');
        }

        return $table
            ->columns([
                TextColumn::make('id')
                    ->searchable(isIndividual: true)
                    ->toggleable(true, true)
                    ->sortable()
                    ->label(__('general.id')),
                TextColumn::make('event.name')
                    ->toggleable(true, true)
                    ->searchable()
                    ->label(__('general.order_event')),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->label(__('general.name'))
                    ->formatStateUsing(fn(string $state) => \Illuminate\Support\Str::limit($state, 40, '...'))
                    ->description(function ($record): string {
                        $flags = array_filter([
                            $record->instant_delivery ? __('general.instant_delivery') : null,
                            $record->dangerous_good ? __('general.dangerous_good') : null,
                            $record->user_note ? __('general.user_note') : null,
                            $record->big_size ? __('general.big_size') : null,
                            $record->needs_truck ? __('general.needs_truck') : null,
                            $record->special_delivery ? __('general.special_delivery') : null,
                            $record->comment ? __('general.comment') : null,
                            $record->delivery_costs ? __('general.delivery_costs') : null,
                            $record->special_flag_text ?? null,
                        ]);

                        return implode(' \ ', $flags);
                    }),
                TextColumn::make('department.name')
                    ->toggleable(true, true)
                    ->searchable()
                    ->label(__('general.department')),
                TextColumn::make('status')
                    ->badge()
                    ->label(__('general.status'))
                    ->sortable()
                    ->toggleable()
                    ->color(fn(string $state): string => match ($state) {
                        'on_hold' => 'gray',
                        'checking' => 'checking',
                        'processing' => 'warning',
                        'open' => 'success',
                        'ordered' => 'info',
                        'delivered' => 'delivered',
                        'partially_received' => 'info',
                        'received' => 'received',
                        'rejected' => 'danger',
                        'locked' => 'danger',
                        'refunded' => 'danger',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'on_hold' => 'heroicon-o-clock',
                        'checking' => 'heroicon-o-arrow-path',
                        'processing' => 'heroicon-o-arrow-path',
                        'open' => 'heroicon-o-check-circle',
                        'ordered' => 'heroicon-o-shopping-cart',
                        'delivered' => 'heroicon-o-truck',
                        'partially_received' => 'heroicon-o-squares-plus',
                        'received' => 'heroicon-o-check',
                        'rejected' => 'heroicon-o-x-circle',
                        'locked' => 'heroicon-o-lock-closed',
                        'refunded' => 'heroicon-o-arrow-uturn-left',
                    })
                    ->formatStateUsing(function ($state) {
                        return strtoupper(str_replace('_', ' ', $state));
                    }),
                TextInputColumn::make('amount')
                    ->label(__('general.quantity'))
                    ->toggleable()
                    ->sortable()
                    ->type('number')
                    ->rules(['numeric', 'min:1', 'max:1000000'])
                    ->disabled(function ($record) {
                        if (Auth::user()->can('can-see-all-departments') & !Auth::user()->can('access-all-departments')) {
                            if ($record->department) {
                                $userDepartments = Auth::user()->departments->pluck('id')->toArray();
                                if (!in_array($record->department->id, $userDepartments)) {
                                    return true;
                                }
                            }
                        }

                        if (Auth::user()->can('can-always-edit-orders')) {
                            return false;
                        }

                        if ($record->status == 'open' && !$record->event->locked) {
                            return false;
                        }

                        return true;
                    }),
                TextColumn::make('price_net')
                    ->label(__('general.total') . ' (' . __('general.net') . ')')
                    ->formatStateUsing(function ($record) {
                        $calculatedPrice = 0;
                        if ($record->price_net) {
                            $calculatedPrice += (float)$record->price_net * (float)$record->amount;
                        }

                        if ($record->discount_net > 0) {
                            $calculatedPrice -= (float)$record->discount_net;
                        }

                        $priceFormatted = number_format($calculatedPrice, 2, ',', '.');

                        $symbol = match ($record->currency) {
                            'EUR' => '€',
                            'USD' => '$',
                            default => '€',
                        };

                        return $priceFormatted . ' ' . $symbol;
                    })
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('price_gross')
                    ->label(__('general.total') . ' (' . __('general.gross') . ')')
                    ->formatStateUsing(function ($record) {
                        $calculatedPrice = 0;
                        if ($record->price_gross) {
                            $calculatedPrice += (float)$record->price_gross * (float)$record->amount;
                        }

                        if ($record->discount_net > 0) {
                            $calculatedPrice -= (float)$record->discount_net * (1 + ((float)$record->tax_rate / 100));
                        }

                        $priceFormatted = number_format($calculatedPrice, 2, ',', '.');

                        $symbol = match ($record->currency) {
                            'EUR' => '€',
                            'USD' => '$',
                            default => '€',
                        };

                        return $priceFormatted . ' ' . $symbol;
                    })
                    ->toggleable(true, true)
                    ->sortable(),
                TextColumn::make('returning_deposit')
                    ->label(__('general.single') . ' (' . __('general.returning_deposit') . ')')
                    ->formatStateUsing(function ($record) {
                        $priceFormatted = number_format($record->returning_deposit, 2, ',', '.');

                        $symbol = match ($record->currency) {
                            'EUR' => '€',
                            'USD' => '$',
                            default => '€',
                        };

                        return $priceFormatted . ' ' . $symbol;
                    })
                    ->toggleable(true, true)
                    ->sortable(),
                TextColumn::make('returning_deposit')
                    ->label(__('general.total') . ' (' . __('general.returning_deposit') . ')')
                    ->formatStateUsing(function ($record) {
                        $calculatedPrice = 0;
                        if ($record->price_gross) {
                            $calculatedPrice += (float)$record->returning_deposit * (float)$record->amount;
                        }

                        $priceFormatted = number_format($calculatedPrice, 2, ',', '.');

                        $symbol = match ($record->currency) {
                            'EUR' => '€',
                            'USD' => '$',
                            default => '€',
                        };

                        return $priceFormatted . ' ' . $symbol;
                    })
                    ->toggleable(true, true)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('general.order_date'))
                    ->date()
                    ->toggleable()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->visible(fn(Order $record): bool => Gate::allows('restore', $record) || Gate::allows('forceDelete', $record) || Gate::allows('bulkForceDelete', $record) || Gate::allows('bulkRestore', $record)),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label(__('general.created_from'))
                            ->placeholder(fn($state): string => 'Dec 18, ' . now()->subYear()->format('Y')),
                        Forms\Components\DatePicker::make('created_until')
                            ->label(__('general.created_until'))
                            ->placeholder(fn($state): string => now()->format('M d, Y')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = __('general.created_from') . ' ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = __('general.created_until') . ' ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
                SelectFilter::make('order_event_id')
                    ->label(__('general.order_event'))
                    ->options(OrderEvent::all(['id', 'name'])->pluck('name', 'id'))
                    ->default(function () {
                        $activeOrderEvent = OrderEvent::where('is_active', true)->first();

                        return $activeOrderEvent ? $activeOrderEvent->id : null;
                    }),
                SelectFilter::make('department_id')
                    ->multiple()
                    ->label(__('general.department'))
                    ->options(function (): array {
                        if (Auth::user()->can('access-all-departments') || Auth::user()->can('can-see-all-departments')) {
                            return Department::all()->pluck('name', 'id')->toArray();
                        } else {
                            return Auth::user()->departments()->pluck('name', 'department_id')->toArray();
                        }
                    }),
                SelectFilter::make('status')
                    ->multiple()
                    ->label(__('general.status'))
                    ->options([
                        'on_hold' => __('general.on_hold'),
                        'checking' => __('general.checking'),
                        'processing' => __('general.processing'),
                        'open' => __('general.open'),
                        'ordered' => __('general.ordered'),
                        'delivered' => __('general.delivered'),
                        'partially_received' => __('general.partially_received'),
                        'received' => __('general.received'),
                        'rejected' => __('general.rejected'),
                        'locked' => __('general.locked'),
                        'refunded' => __('general.refunded'),
                    ]),
                SelectFilter::make('order_request_id')
                    ->label(__('general.linked_request'))
                    ->options([
                        'with' => __('general.yes'),
                        'without' => __('general.no'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === 'with') {
                            return $query->whereNotNull('order_request_id');
                        }

                        if ($data['value'] === 'without') {
                            return $query->whereNull('order_request_id');
                        }

                        return $query;
                    }),
                SelectFilter::make('order_article_id')
                    ->label(__('general.linked_article_directory'))
                    ->options([
                        'with' => __('general.yes'),
                        'without' => __('general.no'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === 'with') {
                            return $query->whereNotNull('order_article_id');
                        }

                        if ($data['value'] === 'without') {
                            return $query->whereNull('order_article_id');
                        }

                        return $query;
                    }),
                SelectFilter::make('url')
                    ->label(__('general.marketplace'))
                    ->multiple()
                    ->options([
                        'frog_store' => __('general.frog_store'),
                        'metro' => __('general.metro'),
                        'amazon' => __('general.amazon'),
                        'hornbach' => __('general.hornbach'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
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
                                        $query->orWhere('url', 'like', '%amazon.%')
                                            ->orWhere('url', 'like', '%amzn.%');
                                    }

                                    if ($value === 'hornbach') {
                                        $query->orWhere('url', 'like', '%hornbach.%');
                                    }
                                }
                            });
                        }


                        return $query;
                    }),
                SelectFilter::make('user_note')
                    ->label(__('general.user_note'))
                    ->options([
                        'with' => __('general.yes'),
                        'without' => __('general.no'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === 'with') {
                            return $query->whereNotNull('user_note');
                        }

                        if ($data['value'] === 'without') {
                            return $query->whereNull('user_note');
                        }

                        return $query;
                    }),
            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(3)
            ->actions([
                ActionGroup::make([
                    ActionGroup::make([
                        Tables\Actions\EditAction::make(),
                        Tables\Actions\DeleteAction::make(),
                        Tables\Actions\RestoreAction::make(),
                        Tables\Actions\ForceDeleteAction::make(),
                        Tables\Actions\ViewAction::make(),
                    ])->dropdown(true),
                    ActionGroup::make([
                        TableAction::make('set_status')
                            ->label(__('general.set_status'))
                            ->action(function (Model $record, array $data): void {
                                $record->update(['status' => $data['status']]);
                            })
                            ->icon('heroicon-o-ellipsis-horizontal-circle')
                            ->form([
                                Select::make('status')
                                    ->label(__('general.status'))
                                    ->options([
                                        'on_hold' => __('general.on_hold'),
                                        'checking' => __('general.checking'),
                                        'processing' => __('general.processing'),
                                        'open' => __('general.open'),
                                        'ordered' => __('general.ordered'),
                                        'delivered' => __('general.delivered'),
                                        'partially_received' => __('general.partially_received'),
                                        'received' => __('general.received'),
                                        'rejected' => __('general.rejected'),
                                        'locked' => __('general.locked'),
                                        'refunded' => __('general.refunded'),
                                    ])
                                    ->prefixIcon('heroicon-o-ellipsis-horizontal-circle')
                                    ->required(),
                            ])
                            ->visible(fn() => Auth::user()->can('can-change-order-status')),
                    ])->dropdown(false),
                    ActionGroup::make([
                        TableAction::make('user_note')
                            ->label(__('general.user_note'))
                            ->action(function (Model $record, array $data): void {
                                $record->update(['user_note' => $data['note']]);
                            })
                            ->icon('heroicon-o-pencil')
                            ->form([
                                Textarea::make('note')
                                    ->label(fn(Model $record) => __('general.user_note') . ' - ' . $record->name)
                                    ->default(fn(Model $record) => $record->user_note)
                                    ->autosize(),
                            ])
                            ->visible(fn(Model $record) => Gate::allows('view', $record)),
                    ])->dropdown(false),
                    ActionGroup::make([
                        TableAction::make('article_directory_link')
                            ->url(function (Model $record) {
                                return route('filament.app.resources.order-articles.view', $record->order_article_id);
                            }, true)
                            ->visible(fn(Model $record) => (!empty($record->order_article_id) && Gate::allows('view-OrderArticle', $record->order_article_id) && OrderArticle::where('id', $record->order_article_id)->exists()))
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->label(__('general.article_directory')),
                        TableAction::make('order_request_link')
                            ->url(function (Model $record) {
                                return route('filament.app.resources.order-requests.view', $record->order_request_id);
                            }, true)
                            ->visible(fn(Model $record) => (!empty($record->order_request_id) && Gate::allows('view-OrderRequest', $record->order_request_id) && OrderRequest::where('id', $record->order_request_id)->exists()))
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->label(__('general.order_request')),
                    ])->dropdown(false)
                ])
            ])
            ->bulkActions([
                BulkAction::make('export_selected')
                    ->label(__('general.export'))
                    ->icon('heroicon-o-printer')
                    ->steps([
                        Step::make(__('general.select_type'))
                            ->schema([
                                Section::make([
                                    Radio::make('export_type')
                                        ->options($export_type_options)
                                        ->descriptions([
                                            'standart' => __('general.export_filetype_standart_description'),
                                            'metro_list' => __('general.metro_list_description'),
                                        ])
                                        ->required()
                                        ->label('')
                                ])
                                    ->description(__('general.type'))
                            ])
                            ->icon('heroicon-o-document'),
                        Step::make('select_columns')
                            ->label(__('Select Columns'))
                            ->description(__('Select the columns you want to export'))
                            ->icon('heroicon-o-list-bullet')
                            ->schema([
                                Checkbox::make('select_all')
                                    ->label(__('general.select_all'))
                                    ->reactive() // Ermöglicht Live-Aktualisierung
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if ($state) {
                                            // If "Select All" is ticked, set all options
                                            $set('columns', array_keys(static::$export_column_options));
                                        } else {
                                            // If "Select All" is ticked off, set empty list
                                            $set('columns', ['id', 'name']);
                                        }
                                    }),
                                Section::make([
                                    CheckboxList::make('columns')
                                        ->label('')
                                        ->options(static::$export_column_options)
                                        ->default(['id', 'name'])
                                        ->columns(3)
                                        ->required()
                                        ->disableOptionWhen(fn(string $value): bool => in_array($value, ['id', 'name'])),
                                ])
                                    ->visible(function (Get $get) {
                                        return $get('export_type') == 'standart';
                                    })
                                    ->description(__('general.select_columns')),
                                Section::make([
                                    Placeholder::make(__('general.no_options_available'))
                                ])
                                    ->visible(function (Get $get) {
                                        return $get('export_type') != 'standart';
                                    })
                            ]),
                        Step::make(__('general.options'))
                            ->schema([
                                #Option for standart export
                                Section::make([
                                    FileUpload::make('image')
                                        ->label('')
                                        ->disk('s3')
                                        ->directory('/export/excel/tmp')
                                        ->visibility('private')
                                        ->image()
                                        ->maxSize(50000)
                                        ->imageEditor()
                                        ->imageEditorMode(1)
                                        ->imageResizeMode('force')
                                        ->imageCropAspectRatio('16:9')
                                        ->avatar()
                                        ->storeFiles(true)
                                        ->imageEditorEmptyFillColor('#000000')
                                        ->getUploadedFileNameForStorageUsing(fn() => str()->random(64))
                                ])
                                    ->description(__('general.picture') . ' - ' . __('general.export_picture_option_description'))
                                    ->visible(function (Get $get) {
                                        return $get('export_type') == 'standart';
                                    }),

                                #Options for standart export
                                Section::make([
                                    Checkbox::make('calculate_total_net')
                                        ->inline()
                                        ->label(__('general.calculate_total_net')),
                                    Checkbox::make('calculate_total_gross')
                                        ->inline()
                                        ->label(__('general.calculate_total_gross')),
                                    Checkbox::make('calculate_total_returning_deposit')
                                        ->inline()
                                        ->label(__('general.calculate_total_returning_deposit')),
                                    Checkbox::make('show_who_added_order')
                                        ->inline()
                                        ->label(__('general.show_who_added_order')),
                                ])
                                    ->description(__('general.extra_folders') . ' - (' . __('general.per_row') . ')')
                                    ->visible(function (Get $get) {
                                        return $get('export_type') == 'standart';
                                    }),

                                #Option for standart export
                                Section::make([
                                    Radio::make('orientation')
                                        ->label('')
                                        ->inline()
                                        ->options([
                                            'portrait' => __('general.portrait'),
                                            'landscape' => __('general.landscape'),
                                        ])
                                        ->default('landscape')
                                        ->required(),
                                ])
                                    ->description(__('general.orientation'))
                                    ->visible(function (Get $get) {
                                        return $get('export_type') == 'standart';
                                    }),

                                #When no option is available
                                Section::make([
                                    Placeholder::make(__('general.no_options_available'))
                                ])
                                    ->visible(function (Get $get) {
                                        return $get('export_type') == 'metro_list';
                                    })
                            ])
                            ->icon('heroicon-o-puzzle-piece'),
                        Step::make(__('general.file_type'))
                            ->schema([
                                Section::make([
                                    Radio::make('file_type')
                                        ->options([
                                            'xlsx' => '.xlsx',
                                            'pdf' => '.pdf',
                                        ])
                                        ->descriptions([
                                            'xlsx' => __('general.excel_table'),
                                            'pdf' => __('general.pdf_file')
                                        ])
                                        ->required()
                                        ->label('')
                                ])->description(__('general.file_type'))
                            ])
                            ->icon('heroicon-o-cog-6-tooth'),
                    ])
                    ->action(function (Collection $records, array $data, $table) {
                        try {
                            if (!empty($data['image'])) {
                                $data['image'] = Storage::temporaryUrl($data['image'], now()->addMinutes(30));
                            }

                            $data['records'] = $records->filter(fn($record) => $record->status !== 'locked');

                            if ($data['records']->count() < 1) {
                                Notification::make()
                                    ->body(__('general.no_entries'))
                                    ->warning()
                                    ->send();
                                return;
                            }

                            //dd($data);

                            $timestamp = Carbon::now('Europe/Berlin')->format('Y_m_d_H_i_s');
                            $exportType = $data['export_type'] ?? 'standart';
                            $fileType = $data['file_type'] ?? 'xlsx';

                            $exportConfig = [
                                #TODO: Metro noch überarbeiten
                                'metro_list' => [
                                    'class' => MetroExport::class,
                                    'filename' => __('general.metro_list') . ' - ' . __('general.orders'),
                                    'params' => [$data['records']],
                                ],
                                'standart' => [
                                    'class' => OrderStandardExport::class,
                                    'filename' => __('general.standart') . ' - ' . __('general.orders'),
                                    'params' => [$data, 92, 92, ['dangerous_good', 'big_size', 'needs_truck', 'booked_to_inventory', 'instant_delivery']],
                                ],
                            ];

                            if (!isset($exportConfig[$exportType])) {
                                return response()->json(['error' => 'Invalid export type'], 400);
                            }

                            $config = $exportConfig[$exportType];
                            $filename = "{$config['filename']} - {$timestamp}.{$fileType}";
                            $exportClass = $config['class'];
                            $exportFormat = $fileType === 'pdf' ? \Maatwebsite\Excel\Excel::MPDF : \Maatwebsite\Excel\Excel::XLSX;

                            return Excel::download(new $exportClass(...$config['params']), $filename, $exportFormat);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->body($e->getMessage() . ' - ' . __('general.reload_required'))
                                ->title(__('general.error'))
                                ->danger()
                                ->persistent()
                                ->send();
                            Log::error('Error: ' . $e->getMessage() . ' - Code: ' . $e->getCode() . ' - File: ' . $e->getFile() . ' - Line: ' . $e->getLine());
                        }
                    }),
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn(Order $record): bool => Gate::allows('bulkDelete', $record)),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn(Order $record): bool => Gate::allows('bulkRestore', $record)),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn(Order $record): bool => Gate::allows('bulkForceDelete', $record)),
                    BulkAction::make('set_status')
                        ->label(__('general.set_status'))
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->update(['status' => $data['status']]);
                            }
                        })
                        ->icon('heroicon-o-ellipsis-horizontal-circle')
                        ->form([
                            Select::make('status')
                                ->label(__('general.status'))
                                ->options([
                                    'on_hold' => __('general.on_hold'),
                                    'checking' => __('general.checking'),
                                    'processing' => __('general.processing'),
                                    'open' => __('general.open'),
                                    'ordered' => __('general.ordered'),
                                    'delivered' => __('general.delivered'),
                                    'partially_received' => __('general.partially_received'),
                                    'received' => __('general.received'),
                                    'rejected' => __('general.rejected'),
                                    'locked' => __('general.locked'),
                                    'refunded' => __('general.refunded'),
                                ])
                                ->prefixIcon('heroicon-o-ellipsis-horizontal-circle')
                                ->required(),
                        ])
                        ->visible(Auth::user()->can('can-change-order-status')),
                    BulkAction::make('set_delivery_address')
                        ->label(__('general.set_delivery_address'))
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->update(['delivery_destination' => $data['delivery_destination']]);
                            }
                            Notification::make()
                                ->body(__('general.saved'))
                                ->success()
                                ->send();
                        })
                        ->icon('heroicon-o-home')
                        ->form([
                            TextArea::make('delivery_destination')
                                ->label(__('general.delivery_destination'))
                                ->rows(7)
                                ->required(),
                        ])
                        ->visible(Auth::user()->can('update-Order')),
                    BulkAction::make('article_number_sync')
                        ->label(__('general.article_number_sync'))
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            foreach ($records as $order) {
                                $orderArticle = $order->directoryArticle;
                                if ($orderArticle) {
                                    $order->article_number = $orderArticle->article_number;
                                    $order->save();
                                }
                            }

                            Notification::make()
                                ->body(__('general.successfully_synchronized'))
                                ->success()
                                ->icon('heroicon-o-check')
                                ->iconColor('success')
                                ->send();
                        })
                        ->visible(Auth::user()->can('can-use-article-directory-special-functions'))
                ]),
            ])
            ->checkIfRecordIsSelectableUsing(
                fn(Model $record): bool => $record->status != 'locked',
            )
            ->groups([
                Group::make('name')
                    ->label(__('general.name'))
                    ->collapsible(),
                Group::make('created_at')
                    ->label(__('general.order_date'))
                    ->date()
                    ->collapsible(),
                Group::make('status')
                    ->label(__('general.status'))
                    ->collapsible(),
                Group::make('department.name')
                    ->label(__('general.department'))
                    ->collapsible(),
            ])
            ->defaultGroup('department.name')
            ->deferLoading()
            ->searchDebounce('750ms')
            ->persistSortInSession()
            ->persistFiltersInSession();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
            'view' => Pages\ViewOrder::route('/{record}')
        ];
    }
}

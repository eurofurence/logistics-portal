<?php

namespace App\Filament\App\Resources\Orders;

use App\Exports\MetroExport;
use App\Exports\OrderStandardExport;
use App\Filament\App\Resources\Orders\Pages\CreateOrder;
use App\Filament\App\Resources\Orders\Pages\EditOrder;
use App\Filament\App\Resources\Orders\Pages\ListOrders;
use App\Filament\App\Resources\Orders\Pages\ViewOrder;
use App\Forms\Components\Timeline;
use App\Models\Department;
use App\Models\Order;
use App\Models\OrderArticle;
use App\Models\OrderEvent;
use App\Models\OrderRequest;
use App\Models\User;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static $export_column_options = [];

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
            __('general.order_event') => $record->event->name,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        $query->when(! $user->can('can-see-all-orders'), function ($query) use ($user) {
            return $query->whereIn('department_id', $user->getDepartmentsWithPermission('view-Order')->pluck('id'));
        });

        return $query;
    }

    /**
     * Checks if the current request route corresponds to the order view page.
     *
     * This static method determines whether the current route name matches
     * the specific route used for viewing an order in the Filament application.
     *
     * @return bool Returns true if the current route is the order view page, false otherwise.
     */
    public static function isView(): bool
    {
        return request()->route()->getName() === 'filament.app.resources.orders.view';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('tabs')
                    ->tabs([
                        Tab::make(__('general.general'))
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
                                                if (self::isView()) {
                                                    return Department::all()->pluck('name', 'id')->toArray();
                                                }

                                                if (Auth::user()->can('can-create-orders-for-other-departments')) {
                                                    return Department::all()->pluck('name', 'id')->toArray();
                                                } else {
                                                    return Auth::user()->getDepartmentsWithPermission('view-Order')->pluck('name', 'id')->toArray();
                                                }
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
                                                    ->hint(__('general.per_item').', '.__('general.gross'))
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

                                                        (float) $max_discount = $get('price_net') * $get('amount');

                                                        if ($max_discount > config('constants.inputs.numeric.max')) {
                                                            return config('constants.inputs.numeric.max');
                                                        }

                                                        return $max_discount;
                                                    })
                                                    ->step(0.01)
                                                    ->hint(__('general.whole_order')),
                                                Section::make(__('general.description'))
                                                    ->schema([
                                                        TextEntry::make('price_description')
                                                            ->state(__('general.price_calculation_description'))
                                                            ->columnSpanFull()
                                                            ->hiddenLabel(true),
                                                        Toggle::make('auto_calculate')
                                                            ->label(__('general.auto_calculate'))
                                                            ->default(true),
                                                    ])
                                                    ->collapsed()
                                                    ->columnSpanFull(),
                                            ])
                                            ->label(__('general.price_and_amount'))
                                            ->columnSpanFull()
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
                                                Fieldset::make('article_and_order_number')
                                                    ->schema([
                                                        TextInput::make('article_number')
                                                            ->label(__('general.article_number'))
                                                            ->maxLength(500),
                                                        TextInput::make('order_number')
                                                            ->label(__('general.order_number'))
                                                            ->maxLength(250),
                                                    ])
                                                    ->columns([
                                                        'default' => 1,
                                                        'sm' => 1,
                                                        'md' => 2,
                                                        'lg' => 2,
                                                    ])
                                                    ->label('')
                                                    ->columnSpanFull(),
                                                Textarea::make('comment')
                                                    ->label(__('general.comment'))
                                                    ->maxLength(100000)
                                                    ->columnSpan(2),
                                            ])
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make(__('general.status'))
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
                                        'refunded' => __('general.refunded'),
                                        'awaiting_approval' => __('general.awaiting_approval'),
                                    ])
                                    ->default('open'),
                                Section::make(__('timeline.status_history'))
                                    ->schema([
                                        Timeline::make('status_timeline'),
                                    ]),
                            ])->visible(Auth::user()->can('can-change-order-status')),
                        Tab::make(__('general.files'))
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
                                    ->disabled(! Auth::user()->can('can-edit-order-files')),
                            ])->visible(Auth::user()->can('can-see-order-files-tab')),
                        Tab::make(__('general.more'))
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
                                                'Bauhaus',
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
                                                'Trans-o-flex',
                                            ]),
                                        TextInput::make('delivery_costs')
                                            ->label(__('general.delivery_costs'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->step(0.01)
                                            ->maxValue(config('constants.inputs.numeric.max'))
                                            ->hint(__('general.gross'))
                                            ->default(0)
                                            ->required(true)
                                            ->suffixIcon('heroicon-m-currency-euro'),
                                        DateTimePicker::make('delivery_date')
                                            ->label(__('general.delivery_date'))
                                            ->seconds(false)
                                            ->timezone('Europe/Berlin')
                                            ->hint('Europe/Berlin'),
                                        Textarea::make('delivery_destination')
                                            ->label(__('general.delivery_destination'))
                                            ->maxLength(10000)
                                            ->autosize(),
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
                                            ->helperText(__('general.delivery_needed_immediate')),
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
                                            ->inline(false),
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
                                            ->maxLength(250),
                                    ])
                                    ->label(__('general.special')),
                                Fieldset::make('')
                                    ->schema([
                                        TextEntry::make('added_by')
                                            ->label(__('general.added_by'))
                                            ->state(fn (Model $record) => $record->addedBy->name),
                                        TextEntry::make('edited_by')
                                            ->label(__('general.edited_by'))
                                            ->state(fn (Model $record) => $record->editedBy->name),
                                        TextEntry::make('created_at')
                                            ->label(__('general.created_at'))
                                            ->state(fn (Model $record) => Carbon::parse($record->created_at)->timezone('Europe/Berlin')),
                                        TextEntry::make('updated_at')
                                            ->label(__('general.updated_at'))
                                            ->state(fn (Model $record) => Carbon::parse($record->updated_at)->timezone('Europe/Berlin')),
                                        TextEntry::make('approved_at')
                                            ->label(__('general.approved_at'))
                                            ->state(function (Model $record) {
                                                if (! empty($record->approved_at)) {
                                                    return Carbon::parse($record->approved_at)->timezone('Europe/Berlin');
                                                }

                                                return '---';
                                            }),
                                        TextEntry::make('approved_by')
                                            ->label(__('general.approved_by'))
                                            ->state(function (Model $record) {
                                                if (! empty($record->approvedBy)) {
                                                    return $record->approvedBy->name;
                                                }

                                                return '---';
                                            }),
                                    ])
                                    ->hiddenOn(CreateOrder::class),
                            ]),
                        Tab::make(__('general.relationships'))
                            ->icon('heroicon-o-link')
                            ->schema([
                                Select::make('order_article_id')
                                    ->label(__('general.article_directory'))
                                    ->getSearchResultsUsing(fn (string $search): array => OrderArticle::withTrashed()
                                        ->where('name', 'like', "%{$search}%")
                                        ->orWhere('id', 'like', "%{$search}%")
                                        ->limit(50)
                                        ->pluck('name', 'id')
                                        ->toArray()
                                    )
                                    ->getOptionLabelUsing(fn ($value): ?string => OrderArticle::withTrashed()->find($value)?->name)
                                    ->searchable()
                                    ->hint(__('general.search_for_name_or_id')),
                                Select::make('order_request_id')
                                    ->label(__('general.order_request'))
                                    ->options(fn () => OrderRequest::withTrashed()
                                        ->get()
                                        ->mapWithKeys(fn ($request) => [$request->id => "{$request->id} - {$request->title}"])
                                        ->toArray()
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->hint(__('general.search_for_name_or_id')),
                            ])
                            ->visible(Auth::user()->can('can-manage-order-relationships'))
                            ->disabled(! Auth::user()->can('can-manage-order-relationships')),
                    ])
                    ->columnSpanFull()
                    ->persistTab(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $export_type_options = ['standard' => __('general.standard')];

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
            'returning_deposit' => __('general.returning_deposit').' ('.__('general.single').')',
            'article_number' => __('general.article_number'),
            'order_number' => __('general.order_number'),
            'approved_at' => __('general.approved_at'),
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
                    ->formatStateUsing(fn (string $state) => Str::limit($state, 40, '...'))
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
                    ->color(fn (string $state): string => match ($state) {
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
                        'awaiting_approval' => 'checking',
                    })
                    ->icon(fn (string $state): string => match ($state) {
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
                        'awaiting_approval' => 'heroicon-o-shield-exclamation'
                    })
                    ->extraAttributes(['class' => 'cursor-pointer'])
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
                        if (Auth::user()->isSuperAdmin()) {
                            return false;
                        }

                        if ($record->department) {
                            if (! Auth::user()->hasDepartmentRoleWithPermissionTo('can-change-amount-order-table', $record->department->id)) {
                                if (! Auth::user()->can('can-change-amount-order-table-all')) {
                                    return true;
                                }
                            }

                            if (Auth::user()->can('can-see-all-orders')) {
                                $userDepartments = Auth::user()->getDepartmentsWithPermission('can-change-amount-order-table')->pluck('id')->toArray();
                                if (! in_array($record->department->id, $userDepartments)) {
                                    if (! Auth::user()->can('can-change-amount-order-table-all')) {
                                        return true;
                                    }
                                }
                            }
                        } else {
                            return true;
                        }

                        if (($record->status == 'open' || $record->status == 'awaiting_approval') && ! $record->event->locked) {
                            return false;
                        } else {
                            if (Auth::user()->can('can-always-edit-orders')) {
                                return false;
                            }
                        }

                        return true;
                    }),
                TextColumn::make('price_net')
                    ->label(__('general.total').' ('.__('general.net').')')
                    ->formatStateUsing(function ($record) {
                        $calculatedPrice = 0;
                        if ($record->price_net) {
                            $calculatedPrice += (float) $record->price_net * (float) $record->amount;
                        }

                        if ($record->discount_net > 0) {
                            $calculatedPrice -= (float) $record->discount_net;
                        }

                        $priceFormatted = number_format($calculatedPrice, 2, ',', '.');

                        $symbol = match ($record->currency) {
                            'EUR' => '€',
                            'USD' => '$',
                            default => '€',
                        };

                        return $priceFormatted.' '.$symbol;
                    })
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('price_gross')
                    ->label(__('general.total').' ('.__('general.gross').')')
                    ->formatStateUsing(function ($record) {
                        $calculatedPrice = 0;
                        if ($record->price_gross) {
                            $calculatedPrice += (float) $record->price_gross * (float) $record->amount;
                        }

                        if ($record->discount_net > 0) {
                            $calculatedPrice -= (float) $record->discount_net * (1 + ((float) $record->tax_rate / 100));
                        }

                        $priceFormatted = number_format($calculatedPrice, 2, ',', '.');

                        $symbol = match ($record->currency) {
                            'EUR' => '€',
                            'USD' => '$',
                            default => '€',
                        };

                        return $priceFormatted.' '.$symbol;
                    })
                    ->toggleable(true, true)
                    ->sortable(),
                TextColumn::make('returning_deposit')
                    ->label(__('general.single').' ('.__('general.returning_deposit').')')
                    ->formatStateUsing(function ($record) {
                        $priceFormatted = number_format($record->returning_deposit, 2, ',', '.');

                        $symbol = match ($record->currency) {
                            'EUR' => '€',
                            'USD' => '$',
                            default => '€',
                        };

                        return $priceFormatted.' '.$symbol;
                    })
                    ->toggleable(true, true)
                    ->sortable(),
                TextColumn::make('returning_deposit')
                    ->label(__('general.total').' ('.__('general.returning_deposit').')')
                    ->formatStateUsing(function ($record) {
                        $calculatedPrice = 0;
                        if ($record->price_gross) {
                            $calculatedPrice += (float) $record->returning_deposit * (float) $record->amount;
                        }

                        $priceFormatted = number_format($calculatedPrice, 2, ',', '.');

                        $symbol = match ($record->currency) {
                            'EUR' => '€',
                            'USD' => '$',
                            default => '€',
                        };

                        return $priceFormatted.' '.$symbol;
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
                TrashedFilter::make()
                    ->visible(fn (): bool => Gate::allows('restore', Order::class) || Gate::allows('forceDelete', Order::class) || Gate::allows('bulkForceDelete', Order::class) || Gate::allows('bulkRestore', Order::class)),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label(__('general.created_from'))
                            ->placeholder(fn ($state): string => 'Dec 18, '.now()->subYear()->format('Y')),
                        DatePicker::make('created_until')
                            ->label(__('general.created_until'))
                            ->placeholder(fn ($state): string => now()->format('M d, Y')),
                        Toggle::make('invert')
                            ->label(__('general.invert')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $from = $data['created_from'] ?? null;
                        $until = $data['created_until'] ?? null;
                        $invert = $data['invert'] ?? false;

                        if (! $from && ! $until) {
                            return $query;
                        }

                        return $query->where(function (Builder $query) use ($from, $until, $invert) {
                            if ($invert) {
                                if ($from) {
                                    $query->orWhereDate('created_at', '<', $from);
                                }
                                if ($until) {
                                    $query->orWhereDate('created_at', '>', $until);
                                }
                            } else {
                                if ($from) {
                                    $query->whereDate('created_at', '>=', $from);
                                }
                                if ($until) {
                                    $query->whereDate('created_at', '<=', $until);
                                }
                            }
                        });
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        $invertText = ($data['invert'] ?? false) ? ' ('.__('general.invert').')' : '';
                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = __('general.created_from').' '.Carbon::parse($data['created_from'])->toFormattedDateString().$invertText;
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = __('general.created_until').' '.Carbon::parse($data['created_until'])->toFormattedDateString().$invertText;
                        }

                        return $indicators;
                    }),
                Filter::make('order_event_id')
                    ->schema([
                        Select::make('value')
                            ->label(__('general.order_event'))
                            ->options(OrderEvent::all(['id', 'name'])->pluck('name', 'id'))
                            ->default(function () {
                                $activeOrderEvent = OrderEvent::where('is_active', true)->first();

                                return $activeOrderEvent ? $activeOrderEvent->id : null;
                            }),
                        Toggle::make('invert')
                            ->label(__('general.invert')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        if ($data['invert'] ?? false) {
                            return $query->where('order_event_id', '!=', $data['value']);
                        }

                        return $query->where('order_event_id', $data['value']);
                    })
                    ->indicateUsing(function (array $data): array {
                        if (empty($data['value'])) {
                            return [];
                        }

                        $indicator = __('general.order_event').': '.(OrderEvent::find($data['value'])?->name ?? $data['value']);

                        if ($data['invert'] ?? false) {
                            $indicator .= ' ('.__('general.invert').')';
                        }

                        return [$indicator];
                    }),
                Filter::make('department_id')
                    ->schema([
                        Select::make('values')
                            ->multiple()
                            ->label(__('general.department'))
                            ->options(function (): array {
                                if (Auth::user()->can('can-see-all-orders')) {
                                    return Department::all()->pluck('name', 'id')->toArray();
                                } else {
                                    return Auth::user()->getDepartmentsWithPermission('view-Order')->pluck('name', 'department_id')->toArray();
                                }
                            }),
                        Toggle::make('invert')
                            ->label(__('general.invert')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        if ($data['invert'] ?? false) {
                            return $query->whereNotIn('department_id', $data['values']);
                        }

                        return $query->whereIn('department_id', $data['values']);
                    })
                    ->indicateUsing(function (array $data): array {
                        if (empty($data['values'])) {
                            return [];
                        }

                        $names = Department::whereIn('id', $data['values'])->pluck('name')->implode(', ');
                        $indicator = __('general.department').': '.$names;

                        if ($data['invert'] ?? false) {
                            $indicator .= ' ('.__('general.invert').')';
                        }

                        return [$indicator];
                    }),
                Filter::make('status')
                    ->schema([
                        Select::make('values')
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
                                'awaiting_approval' => __('general.awaiting_approval'),
                            ]),
                        Toggle::make('invert')
                            ->label(__('general.invert')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        if ($data['invert'] ?? false) {
                            return $query->whereNotIn('status', $data['values']);
                        }

                        return $query->whereIn('status', $data['values']);
                    })
                    ->indicateUsing(function (array $data): array {
                        if (empty($data['values'])) {
                            return [];
                        }

                        $options = [
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
                            'awaiting_approval' => __('general.awaiting_approval'),
                        ];

                        $names = collect($data['values'])->map(fn ($value) => $options[$value] ?? $value)->implode(', ');

                        $indicator = __('general.status').': '.$names;

                        if ($data['invert'] ?? false) {
                            $indicator .= ' ('.__('general.invert').')';
                        }

                        return [$indicator];
                    }),
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
                Filter::make('marketplace')
                    ->schema([
                        Select::make('values')
                            ->label(__('general.marketplace'))
                            ->multiple()
                            ->options([
                                'frog_store' => __('general.frog_store'),
                                'metro' => __('general.metro'),
                                'amazon' => __('general.amazon'),
                                'hornbach' => __('general.hornbach'),
                                'ikea' => __('general.ikea'),
                                'bauhaus' => __('general.bauhaus'),
                            ]),
                        Toggle::make('invert')
                            ->label(__('general.invert')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        $invert = $data['invert'] ?? false;

                        return $query->where(function ($query) use ($data, $invert) {
                            foreach ($data['values'] as $value) {
                                if ($value === 'frog_store') {
                                    $invert ? $query->where('url', 'not like', '%frog_store.%') : $query->orWhere('url', 'like', '%frog_store.%');
                                }

                                if ($value === 'metro') {
                                    $invert ? $query->where('url', 'not like', '%metro.%') : $query->orWhere('url', 'like', '%metro.%');
                                }

                                if ($value === 'amazon') {
                                    if ($invert) {
                                        $query->where('url', 'not like', '%amazon.%')
                                            ->where('url', 'not like', '%amzn.%')
                                            ->where('url', 'not like', '%amzn.eu%');
                                    } else {
                                        $query->orWhere('url', 'like', '%amazon.%')
                                            ->orWhere('url', 'like', '%amzn.%')
                                            ->orWhere('url', 'like', '%amzn.eu%');
                                    }
                                }

                                if ($value === 'hornbach') {
                                    $invert ? $query->where('url', 'not like', '%hornbach.%') : $query->orWhere('url', 'like', '%hornbach.%');
                                }

                                if ($value === 'ikea') {
                                    $invert ? $query->where('url', 'not like', '%ikea.%') : $query->orWhere('url', 'like', '%ikea.%');
                                }

                                if ($value === 'bauhaus') {
                                    $invert ? $query->where('url', 'not like', '%bauhaus.%') : $query->orWhere('url', 'like', '%bauhaus.%');
                                }
                            }
                        });
                    })
                    ->indicateUsing(function (array $data): array {
                        if (empty($data['values'])) {
                            return [];
                        }

                        $options = [
                            'frog_store' => __('general.frog_store'),
                            'metro' => __('general.metro'),
                            'amazon' => __('general.amazon'),
                            'hornbach' => __('general.hornbach'),
                            'ikea' => __('general.ikea'),
                            'bauhaus' => __('general.bauhaus'),
                        ];

                        $names = collect($data['values'])->map(fn ($value) => $options[$value] ?? $value)->implode(', ');
                        $indicator = __('general.marketplace').': '.$names;

                        if ($data['invert'] ?? false) {
                            $indicator .= ' ('.__('general.invert').')';
                        }

                        return [$indicator];
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
                Filter::make('added_by')
                    ->schema([
                        Select::make('values')
                            ->multiple()
                            ->label(__('general.added_by'))
                            ->options(function (): array {
                                return User::all()->pluck('name', 'id')->toArray();
                            }),
                        Toggle::make('invert')
                            ->label(__('general.invert')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        if ($data['invert'] ?? false) {
                            return $query->whereNotIn('added_by', $data['values']);
                        }

                        return $query->whereIn('added_by', $data['values']);
                    })
                    ->indicateUsing(function (array $data): array {
                        if (empty($data['values'])) {
                            return [];
                        }
                        $indicator = __('general.added_by').': '.count($data['values']);
                        if ($data['invert'] ?? false) {
                            $indicator .= ' ('.__('general.invert').')';
                        }

                        return [$indicator];
                    }),
                Filter::make('url')
                    ->schema([
                        TextInput::make('url')
                            ->label(__('general.url')),
                        Toggle::make('invert')
                            ->label(__('general.invert')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['url'])) {
                            return $query;
                        }

                        $invert = $data['invert'] ?? false;

                        return $invert
                            ? $query->where('url', 'not like', '%'.$data['url'].'%')
                            : $query->where('url', 'like', '%'.$data['url'].'%');
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (! empty($data['url'])) {
                            $indicators['url'] = __('general.url').': '.$data['url'].(($data['invert'] ?? false) ? ' ('.__('general.invert').')' : '');
                        }

                        return $indicators;
                    }),
            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(3)
            ->recordActions([
                Action::make('approve')
                    ->label(__('general.approve'))
                    ->action(function (Model $record): void {
                        if ($record->approve() == true) {
                            Notification::make()
                                ->body(__('general.approved'))
                                ->success()
                                ->icon('heroicon-o-check')
                                ->iconColor('success')
                                ->send();
                        }
                    })
                    ->icon('heroicon-o-check')
                    ->size(Size::ExtraLarge)
                    ->requiresConfirmation()
                    ->color(Color::Green)
                    ->modalHeading(__('general.approve_order'))
                    ->modalIcon('heroicon-o-check')
                    ->modalDescription(__('general.approve_order_description'))
                    ->visible(fn (Model $record): bool => $record->canBeApproved()),
                Action::make('decline')
                    ->label('')
                    ->action(function (Model $record): void {
                        if ($record->decline() == true) {
                            Notification::make()
                                ->title(__('general.declined'))
                                ->body(__('general.moved_to_deleted_elements'))
                                ->success()
                                ->icon('heroicon-o-check')
                                ->iconColor('success')
                                ->duration(20000)
                                ->send();
                        }
                    })
                    ->icon('heroicon-o-x-mark')
                    ->size(Size::ExtraLarge)
                    ->color(Color::Red)
                    ->requiresConfirmation()
                    ->modalHeading(__('general.decline_order'))
                    ->modalIcon('heroicon-o-exclamation-triangle')
                    ->visible(fn (Model $record): bool => $record->canBeDeclined()),
                ActionGroup::make([
                    ActionGroup::make([
                        EditAction::make()
                            ->visible(function (Model $record): bool {
                                if (! empty($record->deleted_at)) {
                                    return false;
                                }

                                return true;
                            }),
                        DeleteAction::make()
                            ->modalHeading(function ($record): string {
                                return __('general.delete').': '.$record->name;
                            }),
                        RestoreAction::make()
                            ->visible(function (Model $record) {
                                if ($record->status == 'locked') {
                                    return false;
                                }

                                return true;
                            }),
                        ForceDeleteAction::make(),
                        ViewAction::make()
                            ->visible(function (Model $record): bool {
                                if (! empty($record->deleted_at)) {
                                    return false;
                                }

                                return true;
                            }),
                    ])->dropdown(false),
                    ActionGroup::make([
                        Action::make('set_status')
                            ->label(__('general.set_status'))
                            ->action(function (Model $record, array $data): void {
                                $record->update(['status' => $data['status']]);
                            })
                            ->icon('heroicon-o-ellipsis-horizontal-circle')
                            ->schema([
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
                                        'awaiting_approval' => __('general.awaiting_approval'),
                                    ])
                                    ->prefixIcon('heroicon-o-ellipsis-horizontal-circle')
                                    ->required(),
                            ])
                            ->visible(function (Model $record): bool {
                                return Auth::user()->can('can-change-order-status') || Auth::user()->hasDepartmentRoleWithPermissionTo('can-change-order-status', $record->department->id);
                            }),
                    ])->dropdown(false),
                    ActionGroup::make([
                        Action::make('user_note')
                            ->label(__('general.user_note'))
                            ->action(function (Model $record, array $data): void {
                                $record->update(['user_note' => $data['note']]);
                            })
                            ->icon('heroicon-o-pencil')
                            ->schema([
                                Textarea::make('note')
                                    ->label(fn (Model $record) => __('general.user_note').' - '.$record->name)
                                    ->default(fn (Model $record) => $record->user_note)
                                    ->autosize(),
                            ])
                            ->visible(fn (Model $record) => Gate::allows('view', $record)),
                    ])->dropdown(false),
                    ActionGroup::make([
                        Action::make('article_directory_link')
                            ->url(function (Model $record) {
                                return route('filament.app.resources.order-articles.view', $record->order_article_id);
                            }, true)
                            ->visible(fn (Model $record) => (! empty($record->order_article_id) && Gate::allows('view-OrderArticle', $record->order_article_id) && OrderArticle::where('id', $record->order_article_id)->exists()))
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->label(__('general.article_directory')),
                        Action::make('order_request_link')
                            ->url(function (Model $record) {
                                return route('filament.app.resources.order-requests.view', $record->order_request_id);
                            }, true)
                            ->visible(fn (Model $record) => (! empty($record->order_request_id) && Gate::allows('view-OrderRequest', $record->order_request_id) && OrderRequest::where('id', $record->order_request_id)->exists()))
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->label(__('general.order_request')),
                    ])->dropdown(false),
                ]),
            ])
            ->toolbarActions([
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
                                            'standard' => __('general.export_filetype_standard_description'),
                                            'metro_list' => __('general.metro_list_description'),
                                        ])
                                        ->required()
                                        ->label(''),
                                ])
                                    ->description(__('general.type')),
                            ])
                            ->icon('heroicon-o-document'),
                        Step::make('select_columns')
                            ->label(__('general.select_columns'))
                            ->description(__('general.select_columns_description'))
                            ->icon('heroicon-o-list-bullet')
                            ->schema([
                                Checkbox::make('select_all')
                                    ->label(__('general.select_all'))
                                    ->reactive() // Enables live updating
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
                                        ->columns(3)
                                        ->required()
                                        ->default(['id', 'name'])
                                        ->rules([
                                            function () {
                                                return function (string $attribute, $value, \Closure $fail) {
                                                    $required = ['id', 'name'];
                                                    $missing = array_diff($required, (array) $value);

                                                    $translatedMissing = array_map(
                                                        fn ($key) => static::$export_column_options[$key] ?? $key,
                                                        $missing
                                                    );

                                                    if (count($missing) === 1) {
                                                        $fail(__('middleware.export_field_required', [
                                                            'fields' => implode('', $translatedMissing),
                                                        ]));
                                                    } elseif (count($missing) > 1) {
                                                        $fail(__('middleware.export_fields_required', [
                                                            'fields' => implode(', ', $translatedMissing),
                                                        ]));
                                                    }
                                                };
                                            },
                                        ]),
                                ])
                                    ->visible(function (Get $get) {
                                        return $get('export_type') == 'standard';
                                    })
                                    ->description(__('general.select_columns')),
                                Section::make([
                                    TextEntry::make(__('general.no_options_available')),
                                ])
                                    ->visible(function (Get $get) {
                                        return $get('export_type') != 'standard';
                                    }),
                            ]),
                        Step::make(__('general.options'))
                            ->schema([
                                // Option for standard export
                                /*
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
                                        ->avatar()
                                        ->storeFiles(true)
                                        ->imageEditorEmptyFillColor('#000000')
                                        ->getUploadedFileNameForStorageUsing(fn() => str()->random(64))
                                ])
                                    ->description(__('general.picture') . ' - ' . __('general.export_picture_option_description'))
                                    ->visible(function (Get $get) {
                                        return $get('export_type') == 'standard';
                                    }),
                                */

                                // Options for standard export
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
                                    Checkbox::make('show_who_approved_order')
                                        ->inline()
                                        ->label(__('general.show_who_approved_order')),
                                ])
                                    ->description(__('general.special_fields').' - ('.__('general.per_row').')')
                                    ->visible(function (Get $get) {
                                        return $get('export_type') == 'standard';
                                    }),

                                // Option for standard export
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
                                        return $get('export_type') == 'standard';
                                    }),

                                // When no option is available
                                Section::make([
                                    TextEntry::make(__('general.no_options_available')),
                                ])
                                    ->visible(function (Get $get) {
                                        return $get('export_type') == 'metro_list';
                                    }),
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
                                            'pdf' => __('general.pdf_file'),
                                        ])
                                        ->required()
                                        ->label(''),
                                ])->description(__('general.file_type')),
                            ])
                            ->icon('heroicon-o-cog-6-tooth'),
                    ])
                    ->action(function (Collection $records, array $data, $table) {
                        try {
                            $data['image'] = $data['image'] ?? null;

                            if (! empty($data['image'])) {
                                try {
                                    // Explizit 's3' ansprechen, da der FileUpload ->disk('s3') nutzt
                                    $data['image'] = Storage::disk('s3')->temporaryUrl($data['image'], now()->addMinutes(30));
                                } catch (\Throwable $e) {
                                    // Fallback für lokale Treiber, die keine temporären URLs unterstützen
                                    $data['image'] = Storage::disk(config('filesystems.default'))->path($data['image']);
                                }
                            }

                            $data['records'] = $records->filter(fn ($record) => $record->status !== 'locked');

                            if ($data['records']->count() < 1) {
                                Notification::make()
                                    ->body(__('general.no_entries'))
                                    ->warning()
                                    ->send();

                                return;
                            }

                            // dd($data);

                            $timestamp = Carbon::now('Europe/Berlin')->format('Y_m_d_H_i_s');
                            $exportType = $data['export_type'] ?? 'standard';
                            $fileType = $data['file_type'] ?? 'xlsx';

                            $exportConfig = [
                                'metro_list' => [
                                    'class' => MetroExport::class,
                                    'filename' => __('general.metro_list').' - '.__('general.orders'),
                                    'params' => [$data['records']],
                                ],
                                'standard' => [
                                    'class' => OrderStandardExport::class,
                                    'filename' => __('general.standard').' - '.__('general.orders'),
                                    'params' => [$data, 92, 92, ['dangerous_good', 'big_size', 'needs_truck', 'booked_to_inventory', 'instant_delivery']],
                                ],
                            ];

                            if (! isset($exportConfig[$exportType])) {
                                return response()->json(['error' => 'Invalid export type'], 400);
                            }

                            $config = $exportConfig[$exportType];
                            $filename = "{$config['filename']} - {$timestamp}.{$fileType}";
                            $exportClass = $config['class'];
                            $exportFormat = $fileType === 'pdf' ? \Maatwebsite\Excel\Excel::MPDF : \Maatwebsite\Excel\Excel::XLSX;

                            return Excel::download(new $exportClass(...$config['params']), $filename, $exportFormat);
                        } catch (Exception $e) {
                            Notification::make()
                                ->body($e->getMessage().' - '.__('general.reload_required'))
                                ->title(__('general.error'))
                                ->danger()
                                ->persistent()
                                ->send();
                            Log::error('Error: '.$e->getMessage().' - Code: '.$e->getCode().' - File: '.$e->getFile().' - Line: '.$e->getLine());
                        }
                    }),
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => Gate::allows('bulkDelete', [Auth::user(), Order::class])),
                    RestoreBulkAction::make()
                        ->visible(fn (): bool => Gate::allows('bulkRestore', [Auth::user(), Order::class])),
                    BulkAction::make('set_status')
                        ->label(__('general.set_status'))
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->update(['status' => $data['status']]);
                            }
                        })
                        ->icon('heroicon-o-ellipsis-horizontal-circle')
                        ->schema([
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
                                    'awaiting_approval' => __('general.awaiting_approval'),
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
                        ->schema([
                            Textarea::make('delivery_destination')
                                ->label(__('general.delivery_destination'))
                                ->rows(7),
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
                        ->visible(Auth::user()->can('can-use-article-directory-special-functions')),
                    BulkAction::make('returning_deposit_sync')
                        ->label(__('general.returning_deposit_sync'))
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            foreach ($records as $order) {
                                $orderArticle = $order->directoryArticle;
                                if ($orderArticle) {
                                    $order->returning_deposit = $orderArticle->returning_deposit;
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
                        ->visible(Auth::user()->can('can-use-article-directory-special-functions')),
                    BulkActionGroup::make([
                        BulkAction::make('approve_order')
                            ->label(__('general.approve'))
                            ->icon('heroicon-o-check')
                            ->color(Color::Green)
                            ->requiresConfirmation()
                            ->modalHeading(__('general.approve_order'))
                            ->modalIcon('heroicon-o-check')
                            ->modalDescription(__('general.approve_order_description'))
                            ->action(function (Collection $records) {
                                $approved_elements_counter = 0;

                                foreach ($records as $order) {
                                    if ($order->approve()) {
                                        $approved_elements_counter++;
                                    }
                                }

                                if ($approved_elements_counter > 0) {
                                    Notification::make()
                                        ->body($approved_elements_counter.' '.__('general.approved'))
                                        ->success()
                                        ->icon('heroicon-o-check')
                                        ->iconColor('success')
                                        ->send();

                                    return;
                                }

                                Notification::make()
                                    ->body(__('general.nothing_to_approve'))
                                    ->warning()
                                    ->icon('heroicon-o-exclamation-triangle')
                                    ->iconColor('warning')
                                    ->send();
                            })
                            ->visible(function (): bool {
                                return Auth::user()->hasAnyDepartmentRoleWithPermissionTo('can-approve-orders') || Auth::user()->can('can-approve-orders-for-other-departments');
                            }),
                        BulkAction::make('decline_order')
                            ->label(__('general.decline'))
                            ->icon('heroicon-o-x-mark')
                            ->Color(Color::Red)
                            ->requiresConfirmation()
                            ->modalHeading(__('general.decline_order'))
                            ->modalIcon('heroicon-o-exclamation-triangle')
                            ->action(function (Collection $records) {
                                $declined_elements_counter = 0;

                                foreach ($records as $order) {
                                    if ($order->decline()) {
                                        $declined_elements_counter++;
                                    }
                                }

                                if ($declined_elements_counter > 0) {
                                    Notification::make()
                                        ->title($declined_elements_counter.' '.__('general.declined'))
                                        ->body(__('general.moved_to_deleted_elements'))
                                        ->success()
                                        ->icon('heroicon-o-check')
                                        ->iconColor('success')
                                        ->duration(20000)
                                        ->send();

                                    return;
                                }

                                Notification::make()
                                    ->body(__('general.nothing_to_decline'))
                                    ->warning()
                                    ->icon('heroicon-o-exclamation-triangle')
                                    ->iconColor('warning')
                                    ->send();
                            })
                            ->visible(function (): bool {
                                return Auth::user()->hasAnyDepartmentRoleWithPermissionTo('can-decline-orders') || Auth::user()->can('can-decline-orders-for-other-departments');
                            }),
                    ])
                        ->dropdown(false),
                ]),
            ])
            ->headerActions([
                Action::make('statusDescriptions')
                    ->label(__('general.status_descriptions_title'))
                    ->action(function () {
                        // This action opens up the modal
                    })
                    ->modalContent(function () {
                        return view('components.order_status_description');
                    })
                    ->modalHeading(__('general.status_descriptions_title'))
                    ->icon('heroicon-o-question-mark-circle')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(fn ($action) => $action->label(__('general.close'))),
            ])
            ->checkIfRecordIsSelectableUsing(
                fn (Model $record): bool => $record->status != 'locked',
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
            ->persistSortInSession();
        // ->persistFiltersInSession();
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
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit' => EditOrder::route('/{record}/edit'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}

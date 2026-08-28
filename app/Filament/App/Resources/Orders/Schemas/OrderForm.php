<?php

namespace App\Filament\App\Resources\Orders\Schemas;

use App\Filament\App\Resources\Orders\OrderResource;
use App\Filament\App\Resources\Orders\Pages\CreateOrder;
use App\Forms\Components\Timeline;
use App\Models\Department;
use App\Models\OrderArticle;
use App\Models\OrderEvent;
use App\Models\OrderRequest;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class OrderForm
{
    public static function configure(Schema $schema): Schema
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
                                                if (OrderResource::isView()) {
                                                    return Department::query()->pluck('name', 'id')->toArray();
                                                }

                                                if (Auth::user()->can('can-create-orders-for-other-departments')) {
                                                    return Department::query()->pluck('name', 'id')->toArray();
                                                } else {
                                                    return Auth::user()->getDepartmentsWithPermission('view-Order')->pluck('name', 'id')->toArray();
                                                }
                                            }),
                                        Select::make('order_event_id')
                                            ->label(__('general.order_event'))
                                            ->exists('order_events', 'id')
                                            ->required()
                                            ->options(function (): array {
                                                return OrderEvent::withTrashed()->pluck('name', 'id')->toArray();
                                            })
                                            ->default(function () {
                                                $options = OrderEvent::withTrashed()->pluck('name', 'id')->toArray();

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
                                                            ->default(true)
                                                            ->formatStateUsing(fn ($state) => $state === null || ! $state ? true : $state),
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
                                        'ready_for_pickup' => __('general.ready_for_pickup'),
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
                                            ->hint(__('general.special_flag_text_hint'))
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
}

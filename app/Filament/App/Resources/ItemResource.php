<?php

namespace App\Filament\App\Resources;

use Carbon\Carbon;
use Filament\Forms;
use App\Models\Item;
use Filament\Tables;
use App\Models\Storage;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\BaseUnit;
use Filament\Forms\Form;
use App\Models\Department;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\ItemsOperationSite;
use Filament\Forms\Components\Tabs;
use Filament\Tables\Grouping\Group;
use Illuminate\Support\Facades\Log;
use App\Models\InventorySubCategory;
use Filament\Forms\Components\Radio;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InventoryItemsExport;
use App\View\Components\BarcodeInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Wizard\Step;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Database\Eloquent\Collection;
use Filament\Forms\Components\DateTimePicker;
use App\Actions\Inventory\OperationSiteActions;
use App\Actions\Inventory\SubCategorySiteActions;
use App\Filament\App\Resources\ItemResource\Pages;
use Archilex\ToggleIconColumn\Columns\ToggleIconColumn;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static $export_column_options = array();

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.inventory');

        return static::$navigationGroup;
    }

    public static function getNavigationLabel(): string
    {
        return __('general.inventory_items');
    }

    public static function getModelLabel(): string
    {
        return __('general.inventory_item');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.inventory_items');
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'serialnumber', 'url', 'description', 'owner'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('general.name') => $record->name,
            __('general.department') => $record->connected_department->name,
            __('general.serialnumber') => $record->serialnumber,
            __('general.owner') => $record->owner,
            __('general.description') => $record->description,
            __('general.storage') => $record->connected_storage->name
        ];
    }

    public static function isView(): bool
    {
        return request()->route()->getName() === 'filament.app.resources.items.view';
    }

    public static function isEdit(): bool
    {
        return request()->route()->getName() === 'filament.app.resources.items.edit';
    }

    public static function isCreate(): bool
    {
        return request()->route()->getName() === 'filament.app.resources.items.create';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Tabs::make('tabs')
                            ->tabs([
                                Tabs\Tab::make(__('general.general'))
                                    ->icon('heroicon-m-bars-3')
                                    ->schema([
                                        Forms\Components\Grid::make([
                                            'default' => 1,
                                            'sm' => 1,
                                            'md' => 2,
                                            'lg' => 2,
                                        ])
                                            ->schema([
                                                SpatieMediaLibraryFileUpload::make('main_image')
                                                    ->image()
                                                    ->collection('inventory_main_image')
                                                    ->responsiveImages()
                                                    ->previewable(true)
                                                    ->maxSize(10000)
                                                    ->directory('inventory/main_images')
                                                    ->visibility('private')
                                                    ->openable(),
                                                TextInput::make('name')
                                                    ->label(__('general.name'))
                                                    ->required()
                                                    ->unique(ignoreRecord: true)
                                                    ->maxLength(64),
                                                TextInput::make('shortname')
                                                    ->unique(ignoreRecord: true)
                                                    ->hint(__('general.unique_name'))
                                                    ->label(__('general.shortname'))
                                                    ->visible(false),
                                                Select::make('department')
                                                    ->label(__('general.department'))
                                                    ->id('department')
                                                    ->required()
                                                    ->exists('departments', 'id')
                                                    ->options(function (): array {
                                                        if (self::isView()) {
                                                            return Department::all()->pluck('name', 'id')->toArray();
                                                        }

                                                        if (Auth::user()->can('can-create-items-for-other-departments')) {
                                                            return Department::all()->pluck('name', 'id')->toArray();
                                                        } else {
                                                            return Auth::user()->getDepartmentsWithPermission('view-Item')->pluck('name', 'id')->toArray();
                                                        }
                                                    })
                                                    ->disabled(function () {
                                                        if (self::isEdit() || self::isView()) {
                                                            return true;
                                                        }

                                                        return false;
                                                    })
                                                    ->hint(function () {
                                                        if (self::isEdit() || self::isCreate()) {
                                                            return __('general.inventory_department_note_1');
                                                        }
                                                    }),
                                                Select::make('sub_category')
                                                    ->label(__('general.department_sub_category'))
                                                    ->options(function ($record) {
                                                        if (!empty($record->connected_department->inventory_sub_categories)) {
                                                            return $record->connected_department->inventory_sub_categories->mapWithKeys(function ($subCategory) {
                                                                $departmentName = $subCategory->connected_department ? $subCategory->connected_department->name : __('general.no_department');
                                                                return [$subCategory->id => __('general.id') . ": {$subCategory->id} - {$subCategory->name} ({$departmentName})"];
                                                            })->toArray();
                                                        }
                                                        return [];
                                                    })
                                                    ->searchable(['name'])
                                                    ->live()
                                                    ->preload()
                                                    ->afterStateUpdated(function (Set $set, $state) {
                                                        // Saving the ID and name of the selected element
                                                        $subCategory = InventorySubCategory::find($state);
                                                        $set('current_selected_sub_category_id', $subCategory ? $subCategory->id : null);
                                                        $set('current_selected_sub_category_name', $subCategory ? $subCategory->name : null);
                                                    })
                                                    ->suffixAction(SubCategorySiteActions::getEditAction())
                                                    ->suffixAction(SubCategorySiteActions::getAddAction())
                                                    ->suffixAction(SubCategorySiteActions::getDeleteAction())
                                                    ->disabled(function () {
                                                        if (self::isView() || self::isCreate()) {
                                                            return true;
                                                        }

                                                        return false;
                                                    })
                                                    ->hint(function () {
                                                        if (self::isCreate()) {
                                                            return __('general.sub_category_create_note_1');
                                                        }
                                                    }),
                                                Textarea::make('description')
                                                    ->label(__('general.description'))
                                                    ->maxLength(10000)
                                                    ->columnSpanFull()
                                                    ->rows(5),
                                                Textarea::make('comment')
                                                    ->label(__('general.comment'))
                                                    ->maxLength(100000)
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),
                                Tabs\Tab::make(__('general.details'))
                                    ->icon('heroicon-o-magnifying-glass-circle')
                                    ->schema([
                                        Forms\Components\Grid::make([
                                            'default' => 1,
                                            'sm' => 1,
                                            'md' => 2,
                                            'lg' => 2,
                                        ])->schema([
                                            Select::make('unit')
                                                ->label(__('general.unit'))
                                                ->searchable()
                                                ->options(BaseUnit::all()->pluck('name', 'id'))
                                                ->exists('base_units', 'id')
                                                ->disabled()
                                                ->visible(false),
                                            TextInput::make('price')
                                                ->label(__('general.price'))
                                                ->numeric()
                                                ->minValue(0)
                                                ->step(0.01)
                                                ->maxValue(config('constants.inputs.numeric.max'))
                                                ->default(0)
                                                ->required(false)
                                                ->suffixIcon('heroicon-m-currency-euro'),
                                            TextInput::make('serialnumber')
                                                ->label(__('general.serialnumber'))
                                                ->maxLength(250),
                                            TextInput::make('weight_g')
                                                ->label(__('general.weight'))
                                                ->hint(__('general.in_grams'))
                                                ->maxValue(config('constants.inputs.numeric.max')),
                                            DatePicker::make('due_date')
                                                ->label(__('general.due_date'))
                                                ->timezone('Europe/Berlin')
                                                ->hint('Europe/Berlin'),
                                            BarcodeInput::make('manufacturer_barcode')
                                                ->title(__('general.manufacturer_barcode'))
                                                ->label(__('general.manufacturer_barcode'))
                                                ->icon('heroicon-m-qr-code')
                                                ->maxlength(255)
                                                ->disabled(function () {
                                                    if (self::isView()) {
                                                        return true;
                                                    }

                                                    return false;
                                                }),
                                            TextInput::make('url')
                                                ->label(__('general.url'))
                                                ->url()
                                                ->minLength(4)
                                                ->maxLength(100000)
                                                ->suffixIcon('heroicon-m-globe-alt'),
                                            DateTimePicker::make('buy_date')
                                                ->label(__('general.buy_date'))
                                                ->seconds(false)
                                                ->timezone('Europe/Berlin')
                                                ->hint('Europe/Berlin'),
                                            Textarea::make('owner')
                                                ->label(__('general.owner'))
                                                ->maxlength(10000)
                                                ->rows(5),

                                        ]),
                                    ]),
                                Tabs\Tab::make('storage_and_locations')
                                    ->icon('heroicon-o-building-storefront')
                                    ->label(__('general.storage') . '/' . __('general.locations'))
                                    ->schema([
                                        Select::make('storage')
                                            ->label(__('general.storage'))
                                            ->options(function (): array {
                                                $options = Storage::all(['id', 'name'])->pluck('name', 'id')->toArray();

                                                return $options;
                                            })
                                            ->searchable(['name'])
                                            ->suffixIcon('heroicon-o-building-storefront'),
                                        Select::make('operation_site')
                                            ->label(__('general.operation_site'))
                                            ->options(function ($record): array {
                                                if (empty($record->connected_department)) {
                                                    return [];
                                                }
                                                return $record->connected_department->items_operation_sites->mapWithKeys(function ($operationSite) {
                                                    return [$operationSite->id => __('general.id') . ": {$operationSite->id} - {$operationSite->name} ({$operationSite->connected_department->name})"];
                                                })->toArray();
                                            })
                                            ->searchable(['name'])
                                            ->live()
                                            ->preload()
                                            ->afterStateUpdated(function (Set $set, $state) {
                                                // Saving the ID and name of the selected element
                                                $operationSite = ItemsOperationSite::find($state);
                                                $set('current_selected_operation_site_id', $operationSite ? $operationSite->id : null);
                                                $set('current_selected_operation_site_name', $operationSite ? $operationSite->name : null);
                                            })
                                            ->suffixAction(OperationSiteActions::getEditAction())
                                            ->suffixAction(OperationSiteActions::getAddAction())
                                            ->suffixAction(OperationSiteActions::getDeleteAction())
                                            ->disabled(function () {
                                                if (self::isView() || self::isCreate()) {
                                                    return true;
                                                }

                                                return false;
                                            })
                                            ->hint(function () {
                                                if (self::isEdit()) {
                                                    return __('general.operation_site_create_note_2');
                                                } elseif (self::isCreate()) {
                                                    return __('general.operation_site_create_note_1');
                                                }
                                            }),
                                    ]),
                                Tabs\Tab::make(__('general.more') . '/' . __('general.note'))
                                    ->icon('heroicon-o-ellipsis-horizontal-circle')
                                    ->schema([
                                        Fieldset::make('note')
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
                                                Toggle::make('stackable')
                                                    ->label(__('general.stackable'))
                                                    ->default(false)
                                                    ->inline(false),
                                                DateTimePicker::make('sorted_out')
                                                    ->label(__('general.sorted_out')),
                                                Toggle::make('borrowed_item')
                                                    ->label(__('general.borrowed_item'))
                                                    ->default(false)
                                                    ->inline(false),
                                                Toggle::make('rented_item')
                                                    ->label(__('general.rented_item'))
                                                    ->default(false)
                                                    ->inline(false),
                                                Toggle::make('will_be_brought_to_next_event')
                                                    ->label(__('general.will_be_brought_to_next_event'))
                                                    ->default(false)
                                                    ->inline(false),
                                            ])
                                            ->label(__('general.note')),
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
                                            ->hiddenOn(Pages\CreateItem::class)
                                    ]),
                                Tabs\Tab::make(__('general.files'))
                                    ->icon('heroicon-o-document')
                                    ->schema([
                                        SpatieMediaLibraryFileUpload::make('files')
                                            ->collection('inventory_files')
                                            ->directory('inventory/files')
                                            ->multiple()
                                            ->maxSize(15000)
                                            ->panelLayout('grid')
                                            ->appendFiles()
                                            ->openable()
                                            ->downloadable()
                                            ->previewable()
                                            ->visibility('private'),
                                    ]),
                                Tabs\Tab::make(__('general.qr_code'))
                                    ->icon('heroicon-o-qr-code')
                                    ->schema([
                                        Tabs::make('Tabs')
                                            ->tabs([
                                                Tabs\Tab::make('generate_code')
                                                    ->schema([
                                                        Placeholder::make('WIP')
                                                    ])
                                                    ->label(__('general.generate'))
                                                    ->icon('heroicon-o-plus-circle'),
                                                Tabs\Tab::make('link_code')
                                                    ->schema([
                                                        Placeholder::make('WIP')
                                                    ])
                                                    ->label(__('general.connect'))
                                                    ->disabled()
                                                    ->icon('heroicon-o-link')
                                            ])
                                    ]),
                                Tabs\Tab::make(__('general.custom_fields'))
                                    ->icon('heroicon-o-table-cells')
                                    ->schema([
                                        KeyValue::make('custom_fields')
                                            ->label(__('general.custom_fields'))
                                            ->keyLabel(__('general.field_name'))
                                    ]),
                            ])
                            ->persistTabInQueryString()
                            ->contained(false)
                    ])
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
            'returning_deposit' => __('general.returning_deposit') . ' (' . __('general.single') . ')',
            'article_number' => __('general.article_number'),
            'order_number' => __('general.order_number'),
            'approved_at' => __('general.approved_at'),
        ];

        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable()
                    ->label(__('general.id'))
                    ->toggleable(),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->label(__('general.name'))
                    ->formatStateUsing(fn(string $state) => \Illuminate\Support\Str::limit($state, 40, '...'))
                    ->description(function ($record): string {
                        $flags = array_filter([
                            $record->dangerous_good ? __('general.dangerous_good') : null,
                            $record->borrowed_item ? __('general.borrowed_item') : null,
                            $record->rented_item ? __('general.rented_item') : null,
                            $record->comment ? __('general.comment') : null,
                            $record->due_date ? __('general.due_date') : null,
                        ]);

                        return implode(' \ ', $flags);
                    }),
                TextColumn::make('shortname')
                    ->sortable()
                    ->searchable()
                    ->label(__('general.shortname'))
                    ->toggleable(true, true)
                    ->visible(false),
                TextColumn::make('connected_storage.name')
                    ->sortable()
                    ->searchable()
                    ->label(__('general.name'))
                    ->toggleable(true, true),
                TextColumn::make('connected_department.name')
                    ->sortable()
                    ->searchable()
                    ->label(__('general.department'))
                    ->toggleable(true, true),
                TextColumn::make('connected_storage.name')
                    ->sortable()
                    ->searchable()
                    ->label(__('general.storage'))
                    ->toggleable(),
                ToggleIconColumn::make('sorted_out')
                    ->sortable()
                    ->toggleable(true, true)
                    ->label(__('general.sorted_out'))
                    ->disabled(),
                ToggleIconColumn::make('will_be_brought_to_next_event')
                    ->sortable()
                    ->toggleable(true, false)
                    ->label(__('general.will_be_brought_to_next_event')),
                ToggleIconColumn::make('serialnumber')
                    ->sortable()
                    ->searchable()
                    ->toggleable(true, true)
                    ->label(__('general.serialnumber')),
                /*
                ToggleIconColumn::make('borrowed_item')
                    ->sortable()
                    ->toggleable(true, true)
                    ->label(__('general.borrowed_item')),
                ToggleIconColumn::make('rented_item')
                    ->sortable()
                    ->toggleable(true, true)
                    ->label(__('general.rented_item')),
                */
                TextColumn::make('created_at')
                    ->label(__('general.created_at'))
                    ->date()
                    ->toggleable(true, true)
                    ->sortable(),
                TextColumn::make('manufacturer_barcode')
                    ->label(__('general.manufacturer_barcode'))
                    ->toggleable(true, true)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('connected_operation_site.name')
                    ->label(__('general.operation_site'))
                    ->toggleable(true, true)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('connected_sub_category.name')
                    ->label(__('general.sub_category'))
                    ->toggleable(true, true)
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->visible(fn(Item $record): bool => Gate::allows('restore', $record) || Gate::allows('forceDelete', $record) || Gate::allows('bulkForceDelete', $record) || Gate::allows('bulkRestore', $record)),
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
                Tables\Filters\Filter::make('due_date')
                    ->form([
                        Forms\Components\DatePicker::make('due_date_from')
                            ->label(__('general.due_date_from'))
                            ->placeholder(fn($state): string => 'Dec 18, ' . now()->subYear()->format('Y')),
                        Forms\Components\DatePicker::make('due_date_until')
                            ->label(__('general.due_date_until'))
                            ->placeholder(fn($state): string => now()->format('M d, Y')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['due_date_from'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('due_date', '>=', $date),
                            )
                            ->when(
                                $data['due_date_until'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('due_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['due_date_from'] ?? null) {
                            $indicators['due_date_from'] = __('general.due_date_from') . ' ' . Carbon::parse($data['due_date_from'])->toFormattedDateString();
                        }
                        if ($data['due_date_until'] ?? null) {
                            $indicators['due_date_until'] = __('general.due_date_until') . ' ' . Carbon::parse($data['due_date_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
                SelectFilter::make('department')
                    ->multiple()
                    ->label(__('general.department'))
                    ->options(function (): array {
                        if (Auth::user()->can('can-choose-all-departments') || Auth::user()->can('can-see-all-departments')) {
                            return Department::all()->pluck('name', 'id')->toArray();
                        } else {
                            return Auth::user()->departments()->pluck('name', 'department_id')->toArray();
                        }
                    }),
                SelectFilter::make('storage')
                    ->multiple()
                    ->label(__('general.storage'))
                    ->options(function (): array {
                        if (Auth::user()->can('can-see-all-storages')) {
                            return Storage::all()->pluck('name', 'id')->toArray();
                        } else {
                            // Get the departments to which the user has access
                            $accessibleDepartments = Auth::user()->departments;

                            // Get the storages that belong to these departments
                            $accessibleStorages = Storage::whereHas('managing_department', function ($query) use ($accessibleDepartments) {
                                $query->whereIn('id', $accessibleDepartments->pluck('id'));
                            })->pluck('name', 'id')->toArray();

                            return $accessibleStorages;
                        }
                    }),
                TernaryFilter::make('sorted_out')
                    ->nullable()
                    ->label(__('general.sorted_out')),
                TernaryFilter::make('borrowed_item')
                    ->nullable()
                    ->label(__('general.borrowed_item')),
                TernaryFilter::make('rented_item')
                    ->nullable()
                    ->label(__('general.rented_item')),
                TernaryFilter::make('will_be_brought_to_next_event')
                    ->nullable()
                    ->label(__('general.will_be_brought_to_next_event')),
                TernaryFilter::make('dangerous_good')
                    ->nullable()
                    ->label(__('general.dangerous_good')),
                TernaryFilter::make('big_size')
                    ->nullable()
                    ->label(__('general.big_size')),
                TernaryFilter::make('needs_truck')
                    ->nullable()
                    ->label(__('general.needs_truck')),
                TernaryFilter::make('stackable')
                    ->nullable()
                    ->label(__('general.stackable')),
                SelectFilter::make('operation_site')
                    ->label(__('general.operation_site'))
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(function (): array {
                        $options = array();

                        if (Auth::user()->isSuperAdmin()) {
                            $options = ItemsOperationSite::all()->mapWithKeys(function ($site) {
                                return [$site->id => "ID: {$site->id} - {$site->name} ({$site->connected_department->name})"];
                            })->toArray();
                        } else {
                            $options = [];
                            foreach (Auth::user()->getDepartmentsWithPermission_Array('view-Item') as $department) {
                                $o_sites = ItemsOperationSite::where('department', $department['id'])->get();
                                if ($o_sites->isNotEmpty()) {
                                    foreach ($o_sites as $site) {
                                        $options[$site->id] = "ID: {$site->id} - {$site->name} ({$site->connected_department->name})";
                                    }
                                }
                            }
                        }

                        return $options;
                    }),
                SelectFilter::make('sub_category')
                    ->label(__('general.sub_category'))
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(function (): array {
                        if (Auth::user()->isSuperAdmin()) {
                            // Hole alle Sub-Kategorien und formatiere sie für SuperAdmins
                            $options = InventorySubCategory::all()->mapWithKeys(function ($subCategory) {
                                $departmentName = $subCategory->connected_department ? $subCategory->connected_department->name : 'No Department';
                                return [$subCategory->id => "ID: {$subCategory->id} - {$subCategory->name} ({$departmentName})"];
                            })->toArray();
                        } else {
                            $options = [];
                            foreach (Auth::user()->getDepartmentsWithPermission_Array('view-Item') as $department) {
                                // Angenommen, es gibt eine Beziehung oder Methode, um Sub-Kategorien einer Abteilung zu erhalten
                                $subCategories = InventorySubCategory::where('department_id', $department['id'])->get();
                                if ($subCategories->isNotEmpty()) {
                                    foreach ($subCategories as $subCategory) {
                                        $departmentName = $subCategory->connected_department ? $subCategory->connected_department->name : 'No Department';
                                        $options[$subCategory->id] = "ID: {$subCategory->id} - {$subCategory->name} ({$departmentName})";
                                    }
                                }
                            }
                        }
                        return $options;
                    })
            ])
            ->filtersFormColumns(3)
            ->actions([
                ActionGroup::make([
                    Tables\Actions\Action::make('show_storage_location_action')
                        ->url(function (Model $record) {
                            return route('filament.app.resources.storages.view', $record->storage);
                        }, true)
                        ->visible(fn(Model $record) => (!empty($record->storage) && Gate::allows('view-Storage', $record->storage) && Storage::where('id', $record->storage)->exists()))
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->label(__('general.open_storage')),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->modalHeading(function ($record): string {
                            return __('general.delete') . ': ' . $record->name;
                        }),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export_selected')
                    ->label(__('general.export'))
                    ->color('primary')
                    ->disabled()
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
                                        return $get('export_type') == 'standard';
                                    })
                                    ->description(__('general.select_columns')),
                                Section::make([
                                    Placeholder::make(__('general.no_options_available'))
                                ])
                                    ->visible(function (Get $get) {
                                        return $get('export_type') != 'standard';
                                    })
                            ]),
                        Step::make(__('general.options'))
                            ->schema([
                                #Option for standard export
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
                                        return $get('export_type') == 'standard';
                                    }),

                                #Options for standard export
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
                                    ->description(__('general.special_fields') . ' - (' . __('general.per_row') . ')')
                                    ->visible(function (Get $get) {
                                        return $get('export_type') == 'standard';
                                    }),

                                #Option for standard export
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

                            $timestamp = Carbon::now('Europe/Berlin')->format('Y_m_d_H_i_s');
                            $exportType = $data['export_type'] ?? 'standard';
                            $fileType = $data['file_type'] ?? 'xlsx';

                            $exportConfig = [
                                'standard' => [
                                    'class' => InventoryItemsExport::class,
                                    'filename' => __('general.standard') . ' - ' . __('general.orders'),
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
                        ->visible(Gate::check('bulkDelete', Item::class)),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(Gate::check('bulkRestore', Item::class)),
                    Tables\Actions\BulkAction::make('setWillBeBroughtToNextEvent')
                        ->label(__('general.will_be_brought_along'))
                        ->action(function (Collection $records) {
                            $records->each->update(['will_be_brought_to_next_event' => true]);

                            Notification::make()
                                ->body(__('general.saved'))
                                ->success()
                                ->send();
                        })
                        ->icon('heroicon-o-check-circle'),
                    Tables\Actions\BulkAction::make('unsetWillBeBroughtToNextEvent')
                        ->label(__('general.will_not_be_brought_along'))
                        ->action(function (Collection $records) {
                            $records->each->update(['will_be_brought_to_next_event' => false]);

                            Notification::make()
                                ->body(__('general.saved'))
                                ->success()
                                ->send();
                        })
                        ->icon('heroicon-o-x-circle'),
                ]),
            ])
            ->groups([
                Group::make('name')
                    ->label(__('general.name'))
                    ->collapsible(),
                Group::make('will_be_brought_to_next_event')
                    ->label(__('general.will_be_brought_to_next_event'))
                    ->getTitleFromRecordUsing(function (Item $record): string {
                        if ($record->will_be_brought_to_next_event) {
                            return __('general.yes');
                        }

                        return __('general.no');
                    })
                    ->collapsible(),
                Group::make('connected_department.name')
                    ->label(__('general.department'))
                    ->collapsible(),
                Group::make('created_at')
                    ->label(__('general.created_at'))
                    ->date()
                    ->collapsible(),
                Group::make('connected_operation_site.name')
                    ->label(__('general.operation_site'))
                    ->getTitleFromRecordUsing(function (Item $record): string {
                        if (!empty($record->connected_operation_site)) {
                            return ucfirst($record->connected_operation_site->name) . " ({$record->connected_department->name})";
                        }

                        return __('general.no_operation_site');
                    })
                    ->collapsible(),
                Group::make('connected_sub_category.name')
                    ->label(__('general.sub_category'))
                    ->getTitleFromRecordUsing(function (Item $record): string {
                        if (!empty($record->connected_sub_category)) {
                            return ucfirst($record->connected_sub_category->name) . " ({$record->connected_department->name})";
                        }

                        return __('general.no_category');
                    })
                    ->collapsible(),
            ]);
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
            'index' => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
            'view' => Pages\ViewItem::route('/{record}'),
        ];
    }
}

<?php

namespace App\Filament\App\Resources\Items\Schemas;

use App\Actions\Inventory\OperationSiteActions;
use App\Actions\Inventory\SubCategorySiteActions;
use App\Filament\App\Resources\Items\ItemResource;
use App\Filament\App\Resources\Items\Pages\CreateItem;
use App\Models\BaseUnit;
use App\Models\Department;
use App\Models\InventorySubCategory;
use App\Models\Item;
use App\Models\ItemsOperationSite;
use App\Models\Storage;
use App\View\Components\BarcodeInput;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
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
                                                    ->unique(
                                                        ignoreRecord: true,
                                                        modifyRuleUsing: function (Unique $rule, Get $get): Unique {
                                                            return $rule->where('department', $get('department'));
                                                        },
                                                    )
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
                                                        if (ItemResource::isView()) {
                                                            return Department::query()->pluck('name', 'id')->toArray();
                                                        }

                                                        if (Auth::user()->can('can-create-items-for-other-departments')) {
                                                            return Department::query()->pluck('name', 'id')->toArray();
                                                        } else {
                                                            return Auth::user()->getDepartmentsWithPermission('view-Item')->pluck('name', 'id')->toArray();
                                                        }
                                                    })
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set) {
                                                        $set('storage', null);
                                                        $set('sub_category', null);
                                                        $set('operation_site', null);
                                                    })
                                                    ->disabled(function () {
                                                        if (ItemResource::isEdit() || ItemResource::isView()) {
                                                            return true;
                                                        }

                                                        return false;
                                                    })
                                                    ->hint(function () {
                                                        if (ItemResource::isEdit() || ItemResource::isCreate()) {
                                                            return __('general.inventory_department_note_1');
                                                        }
                                                    }),
                                                Select::make('sub_category')
                                                    ->label(__('general.department_sub_category'))
                                                    ->options(function (Get $get) {
                                                        $departmentId = $get('department');
                                                        if ($departmentId) {
                                                            $department = Department::find($departmentId);
                                                            if ($department) {
                                                                return $department->inventory_sub_categories->pluck('name', 'id')->toArray();
                                                            }
                                                        }

                                                        return [];
                                                    })
                                                    ->searchable(['name'])
                                                    ->live()
                                                    ->afterStateHydrated(function (Set $set, mixed $state): void {
                                                        $subCategory = InventorySubCategory::find($state);
                                                        $set('current_selected_sub_category_id', $subCategory?->id);
                                                        $set('current_selected_sub_category_name', $subCategory?->name);
                                                    })
                                                    ->preload()
                                                    ->afterStateUpdated(function (Set $set, $state) {
                                                        // Saving the ID and name of the selected element
                                                        $subCategory = InventorySubCategory::find($state);
                                                        $set('current_selected_sub_category_id', $subCategory ? $subCategory->id : null);
                                                        $set('current_selected_sub_category_name', $subCategory ? $subCategory->name : null);
                                                    })
                                                    ->suffixAction(fn (Get $get) => SubCategorySiteActions::getAddAction($get('department')))
                                                    ->suffixAction(fn (Get $get) => SubCategorySiteActions::getEditAction($get('department')))
                                                    ->suffixAction(fn (Get $get) => SubCategorySiteActions::getDeleteAction($get('department')))
                                                    ->disabled(function (Get $get) {
                                                        return ItemResource::isView() || ItemResource::isCreate() || ! $get('department');
                                                    })
                                                    ->hint(function (Get $get) {
                                                        if (! $get('department')) {
                                                            return __('general.please_select_department_first');
                                                        }
                                                        if (ItemResource::isCreate()) {
                                                            return __('general.sub_category_create_note_1');
                                                        }
                                                    })
                                                    ->hintIcon(function (Get $get) {
                                                        if (! $get('department')) {
                                                            return 'heroicon-o-exclamation-triangle';
                                                        }

                                                        return null;
                                                    }),
                                                TextInput::make('quantity')
                                                    ->label(__('general.quantity'))
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->maxValue(config('constants.inputs.numeric.max'))
                                                    ->required(false),
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
                                Tab::make(__('general.details'))
                                    ->icon('heroicon-o-magnifying-glass-circle')
                                    ->schema([
                                        Grid::make([
                                            'default' => 1,
                                            'sm' => 1,
                                            'md' => 2,
                                            'lg' => 2,
                                        ])->schema([
                                            Select::make('unit')
                                                ->label(__('general.unit'))
                                                ->searchable()
                                                ->options(BaseUnit::query()->pluck('name', 'id'))
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
                                            TextInput::make('palletnumber')
                                                ->label(__('general.palletnumber'))
                                                ->maxLength(255),
                                            TextInput::make('serialnumber')
                                                ->label(__('general.serialnumber'))
                                                ->maxLength(250),
                                            TextInput::make('weight')
                                                ->label(__('general.weight'))
                                                ->maxLength(250),
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
                                                    if (ItemResource::isView()) {
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
                                Tab::make('storage_and_locations')
                                    ->icon('heroicon-o-building-storefront')
                                    ->label(__('general.storage').'/'.__('general.locations'))
                                    ->schema([
                                        Select::make('storage')
                                            ->label(__('general.storage'))
                                            ->options(function (Get $get): array {
                                                $user = Auth::user();
                                                $departmentId = $get('department');

                                                $query = Storage::query();

                                                $query->where(function ($q) use ($departmentId) {
                                                    $q->where('type', 1); // Global storages

                                                    if ($departmentId) {
                                                        $q->orWhere(function ($q2) use ($departmentId) {
                                                            $q2->where('type', 2) // Department specific
                                                                ->where(function ($subQ) use ($departmentId) {
                                                                    $subQ->where('managing_department', $departmentId)
                                                                        ->orWhereHas('departments', function ($deptQuery) use ($departmentId) {
                                                                            $deptQuery->where('department', $departmentId);
                                                                        });
                                                                });
                                                        });
                                                    }
                                                });

                                                return $query->pluck('name', 'id')->toArray();
                                            })
                                            ->searchable(['name'])
                                            ->hint(function (Get $get) {
                                                if (! $get('department')) {
                                                    return __('general.storage_department_hint');
                                                }

                                                return null;
                                            })
                                            ->hintIcon(function (Get $get) {
                                                if (! $get('department')) {
                                                    return 'heroicon-o-exclamation-triangle';
                                                }

                                                return null;
                                            })
                                            ->suffixIcon('heroicon-o-building-storefront'),
                                        Select::make('operation_site')
                                            ->label(__('general.operation_site'))
                                            ->options(function (Get $get): array {
                                                $departmentId = $get('department');
                                                if ($departmentId) {
                                                    $department = Department::find($departmentId);
                                                    if ($department) {
                                                        return $department->items_operation_sites->pluck('name', 'id')->toArray();
                                                    }
                                                }

                                                return [];
                                            })
                                            ->searchable(['name'])
                                            ->live()
                                            ->afterStateHydrated(function (Set $set, mixed $state): void {
                                                $operationSite = ItemsOperationSite::find($state);
                                                $set('current_selected_operation_site_id', $operationSite?->id);
                                                $set('current_selected_operation_site_name', $operationSite?->name);
                                            })
                                            ->preload()
                                            ->afterStateUpdated(function (Set $set, $state) {
                                                // Saving the ID and name of the selected element
                                                $operationSite = ItemsOperationSite::find($state);
                                                $set('current_selected_operation_site_id', $operationSite ? $operationSite->id : null);
                                                $set('current_selected_operation_site_name', $operationSite ? $operationSite->name : null);
                                            })
                                            ->suffixAction(fn (Get $get) => OperationSiteActions::getAddAction($get('department')))
                                            ->suffixAction(fn (Get $get) => OperationSiteActions::getEditAction($get('department')))
                                            ->suffixAction(fn (Get $get) => OperationSiteActions::getDeleteAction($get('department')))
                                            ->disabled(function (Get $get) {
                                                return ItemResource::isView() || ItemResource::isCreate() || ! $get('department');
                                            })
                                            ->hint(function (Get $get) {
                                                if (! $get('department')) {
                                                    return __('general.please_select_department_first');
                                                }
                                                if (ItemResource::isEdit()) {
                                                    return __('general.operation_site_create_note_2');
                                                } elseif (ItemResource::isCreate()) {
                                                    return __('general.operation_site_create_note_1');
                                                }
                                            })
                                            ->hintIcon(function (Get $get) {
                                                if (! $get('department')) {
                                                    return 'heroicon-o-exclamation-triangle';
                                                }

                                                return null;
                                            }),
                                    ]),
                                Tab::make(__('general.more').'/'.__('general.note'))
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
                                                TextInput::make('special_flag_text')
                                                    ->label(__('general.special_flag_text'))
                                                    ->hint(__('general.special_flag_text_hint'))
                                                    ->maxLength(250),
                                            ])
                                            ->label(__('general.note')),
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
                                            ])
                                            ->hiddenOn(CreateItem::class),
                                    ]),
                                Tab::make(__('general.files'))
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
                                Tab::make(__('general.qr_code'))
                                    ->icon('heroicon-o-qr-code')
                                    ->visible(auth()->user()->isSuperAdmin())
                                    ->schema([
                                        Tabs::make('Tabs')
                                            ->tabs([
                                                Tab::make('generate_code')
                                                    ->schema([
                                                        // Placeholder::make('WIP')
                                                    ])
                                                    ->label(__('general.generate'))
                                                    ->icon('heroicon-o-plus-circle'),
                                                Tab::make('link_code')
                                                    ->schema([
                                                        // Placeholder::make('WIP')
                                                    ])
                                                    ->label(__('general.connect'))
                                                    ->disabled()
                                                    ->icon('heroicon-o-link'),
                                            ]),
                                    ]),
                                Tab::make(__('general.custom_fields'))
                                    ->icon('heroicon-o-table-cells')
                                    ->schema([
                                        KeyValue::make('custom_fields')
                                            ->label(__('general.custom_fields'))
                                            ->keyLabel(__('general.field_name')),
                                    ]),
                            ])
                            ->persistTabInQueryString()
                            ->contained(false),
                    ])->columnSpanFull(),
            ]);
    }
}

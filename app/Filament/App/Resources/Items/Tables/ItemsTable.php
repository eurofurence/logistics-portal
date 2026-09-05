<?php

namespace App\Filament\App\Resources\Items\Tables;

use App\Exports\InventoryItemsExport;
use App\Models\Department;
use App\Models\InventorySubCategory;
use App\Models\Item;
use App\Models\ItemsOperationSite;
use App\Models\Storage;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Maatwebsite\Excel\Facades\Excel;

class ItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::getColumns())
            ->filters(self::getFilters())
            ->filtersFormColumns(3)
            ->recordActions(self::getRecordActions())
            ->toolbarActions(self::getToolbarActions())
            ->groups(self::getGroups());
    }

    public static function getColumns(): array
    {
        return [
            TextColumn::make('id')
                ->sortable()
                ->searchable()
                ->label(__('general.id'))
                ->toggleable(),
            TextColumn::make('quantity')
                ->label(__('general.quantity'))
                ->toggleable()
                ->sortable()
                ->searchable(),
            SpatieMediaLibraryImageColumn::make('main_image')
                ->collection('inventory_main_image')
                ->label(__('general.picture'))
                ->width(100)
                ->imageHeight(100)
                ->toggleable(),
            TextColumn::make('name')
                ->sortable()
                ->searchable()
                ->label(__('general.name'))
                ->formatStateUsing(fn (string $state) => Str::limit($state, 40, '...'))
                ->description(function ($record): string {
                    $flags = array_filter([
                        $record->dangerous_good ? __('general.dangerous_good') : null,
                        $record->borrowed_item ? __('general.borrowed_item') : null,
                        $record->rented_item ? __('general.rented_item') : null,
                        $record->user_note ? __('general.user_note') : null,
                        $record->special_flag_text ?: null,
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
            /*
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
                */
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
            TextColumn::make('palletnumber')
                ->label(__('general.palletnumber'))
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
        ];
    }

    public static function getFilters(): array
    {
        return [
            TrashedFilter::make()
                ->visible(fn (): bool => Gate::allows('restore', Item::class) || Gate::allows('forceDelete', Item::class) || Gate::allows('bulkForceDelete', Item::class) || Gate::allows('bulkRestore', Item::class)),
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
            Filter::make('due_date')
                ->form([
                    DatePicker::make('due_date_from')
                        ->label(__('general.due_date_from'))
                        ->placeholder(fn ($state): string => 'Dec 18, '.now()->subYear()->format('Y')),
                    DatePicker::make('due_date_until')
                        ->label(__('general.due_date_until'))
                        ->placeholder(fn ($state): string => now()->format('M d, Y')),
                    Toggle::make('invert')
                        ->label(__('general.invert')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    $from = $data['due_date_from'] ?? null;
                    $until = $data['due_date_until'] ?? null;
                    $invert = $data['invert'] ?? false;

                    if (! $from && ! $until) {
                        return $query;
                    }

                    return $query->where(function (Builder $query) use ($from, $until, $invert) {
                        if ($invert) {
                            if ($from) {
                                $query->orWhereDate('due_date', '<', $from);
                            }
                            if ($until) {
                                $query->orWhereDate('due_date', '>', $until);
                            }
                        } else {
                            if ($from) {
                                $query->whereDate('due_date', '>=', $from);
                            }
                            if ($until) {
                                $query->whereDate('due_date', '<=', $until);
                            }
                        }
                    });
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];
                    $invertText = ($data['invert'] ?? false) ? ' ('.__('general.invert').')' : '';
                    if ($data['due_date_from'] ?? null) {
                        $indicators['due_date_from'] = __('general.due_date_from').' '.Carbon::parse($data['due_date_from'])->toFormattedDateString().$invertText;
                    }
                    if ($data['due_date_until'] ?? null) {
                        $indicators['due_date_until'] = __('general.due_date_until').' '.Carbon::parse($data['due_date_until'])->toFormattedDateString().$invertText;
                    }

                    return $indicators;
                }),
            Filter::make('department')
                ->form([
                    Select::make('values')
                        ->multiple()
                        ->label(__('general.department'))
                        ->options(function (): array {
                            if (Auth::user()->can('can-choose-all-departments') || Auth::user()->can('can-see-all-departments')) {
                                return Department::query()->pluck('name', 'id')->toArray();
                            } else {
                                return Auth::user()->departments()->pluck('name', 'department_id')->toArray();
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
                        return $query->whereNotIn('department', $data['values']);
                    }

                    return $query->whereIn('department', $data['values']);
                })
                ->indicateUsing(function (array $data): array {
                    if (empty($data['values'])) {
                        return [];
                    }
                    $indicator = __('general.department').': '.count($data['values']);
                    if ($data['invert'] ?? false) {
                        $indicator .= ' ('.__('general.invert').')';
                    }

                    return [$indicator];
                }),
            Filter::make('storage')
                ->form([
                    Select::make('values')
                        ->multiple()
                        ->label(__('general.storage'))
                        ->options(function (): array {
                            if (Auth::user()->can('can-see-all-storages')) {
                                return Storage::query()->pluck('name', 'id')->toArray();
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
                    Toggle::make('invert')
                        ->label(__('general.invert')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    if (empty($data['values'])) {
                        return $query;
                    }
                    if ($data['invert'] ?? false) {
                        return $query->whereNotIn('storage', $data['values']);
                    }

                    return $query->whereIn('storage', $data['values']);
                })
                ->indicateUsing(function (array $data): array {
                    if (empty($data['values'])) {
                        return [];
                    }
                    $indicator = __('general.storage').': '.count($data['values']);
                    if ($data['invert'] ?? false) {
                        $indicator .= ' ('.__('general.invert').')';
                    }

                    return [$indicator];
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
            Filter::make('operation_site')
                ->form([
                    Select::make('values')
                        ->label(__('general.operation_site'))
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(function (): array {
                            $options = [];

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
                    Toggle::make('invert')
                        ->label(__('general.invert')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    if (empty($data['values'])) {
                        return $query;
                    }
                    if ($data['invert'] ?? false) {
                        return $query->whereNotIn('operation_site', $data['values']);
                    }

                    return $query->whereIn('operation_site', $data['values']);
                })
                ->indicateUsing(function (array $data): array {
                    if (empty($data['values'])) {
                        return [];
                    }
                    $indicator = __('general.operation_site').': '.count($data['values']);
                    if ($data['invert'] ?? false) {
                        $indicator .= ' ('.__('general.invert').')';
                    }

                    return [$indicator];
                }),
            Filter::make('sub_category')
                ->form([
                    Select::make('values')
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
                                    $subCategories = InventorySubCategory::where('department', $department['id'])->get();
                                    if ($subCategories->isNotEmpty()) {
                                        foreach ($subCategories as $subCategory) {
                                            $departmentName = $subCategory->connected_department ? $subCategory->connected_department->name : 'No Department';
                                            $options[$subCategory->id] = "ID: {$subCategory->id} - {$subCategory->name} ({$departmentName})";
                                        }
                                    }
                                }
                            }

                            return $options;
                        }),
                    Toggle::make('invert')
                        ->label(__('general.invert')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    if (empty($data['values'])) {
                        return $query;
                    }
                    if ($data['invert'] ?? false) {
                        return $query->whereNotIn('sub_category', $data['values']);
                    }

                    return $query->whereIn('sub_category', $data['values']);
                })
                ->indicateUsing(function (array $data): array {
                    if (empty($data['values'])) {
                        return [];
                    }
                    $indicator = __('general.sub_category').': '.count($data['values']);
                    if ($data['invert'] ?? false) {
                        $indicator .= ' ('.__('general.invert').')';
                    }

                    return [$indicator];
                }),
        ];
    }

    public static function getRecordActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('show_storage_location_action')
                    ->url(function (Model $record) {
                        return route('filament.app.resources.storages.view', $record->storage);
                    }, true)
                    ->visible(fn (Model $record) => (! empty($record->storage) && Gate::allows('view-Storage', $record->storage) && Storage::where('id', $record->storage)->exists()))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->label(__('general.open_storage')),
                ViewAction::make(),
                Action::make('user_note')
                    ->label(__('general.user_note'))
                    ->action(function (Model $record, array $data): void {
                        $record->update(['user_note' => $data['note']]);
                    })
                    ->icon('heroicon-o-pencil')
                    ->schema([
                        Textarea::make('note')
                            ->label(fn (Model $record): string => __('general.user_note').' - '.$record->name)
                            ->default(fn (Model $record): ?string => $record->user_note)
                            ->autosize(),
                    ])
                    ->visible(fn (Model $record): bool => Gate::allows('view', $record)),
                ReplicateAction::make()
                    ->icon('heroicon-o-arrow-up-on-square-stack')
                    ->schema([
                        TextEntry::make('duplicate_hint')
                            ->label(__('general.hint'))
                            ->state(__('general.duplicate_note_1')),
                        TextInput::make('name')
                            ->label(__('general.name'))
                            ->required()
                            ->maxLength(64)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: function (Unique $rule, Get $get): Unique {
                                    return $rule->where('department', $get('department'));
                                },
                            ),
                    ])
                    ->successRedirectUrl(fn (Model $replica): string => route('filament.app.resources.items.edit', $replica))
                    ->successNotificationTitle(__('general.entry_duplicated')),
                EditAction::make(),
                DeleteAction::make()
                    ->modalHeading(function ($record): string {
                        return __('general.delete').': '.$record->name;
                    }),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ]),
        ];
    }

    public static function getToolbarActions(): array
    {
        $exportTypeOptions = ['standard' => __('general.standard')];

        $exportColumnOptions = [
            'id' => __('general.id'),
            'name' => __('general.name'),
            'serialnumber' => __('general.serialnumber'),
            'weight' => __('general.weight'),
            'stackable' => __('general.stackable'),
            'due_date' => __('general.due_date'),
            'sorted_out' => __('general.sorted_out'),
            'description' => __('general.description'),
            'comment' => __('general.comment'),
            'user_note' => __('general.user_note'),
            'special_flag_text' => __('general.special_flag_text'),
            'price' => __('general.price'),
            'buy_date' => __('general.buy_date'),
            'dangerous_good' => __('general.dangerous_good'),
            'big_size' => __('general.big_size'),
            'url' => __('general.url'),
            'needs_truck' => __('general.needs_truck'),
            'created_at' => __('general.created_at'),
            'updated_at' => __('general.updated_at'),
            'owner' => __('general.owner'),
            'borrowed_item' => __('general.borrowed_item'),
            'rented_item' => __('general.rented_item'),
            'will_be_brought_to_next_event' => __('general.will_be_brought_to_next_event'),
            'manufacturer_barcode' => __('general.manufacturer_barcode'),
        ];

        return [
            BulkAction::make('export_selected')
                ->label(__('general.export'))
                ->color('primary')
                ->icon('heroicon-o-printer')
                ->steps([
                    Step::make(__('general.select_type'))
                        ->schema([
                            Section::make([
                                Radio::make('export_type')
                                    ->options($exportTypeOptions)
                                    ->descriptions([
                                        'standard' => __('general.export_filetype_standard_description'),
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
                                ->afterStateUpdated(function (callable $set, $state) use ($exportColumnOptions) {
                                    if ($state) {
                                        // If "Select All" is ticked, set all options
                                        $set('columns', array_keys($exportColumnOptions));
                                    } else {
                                        // If "Select All" is ticked off, set empty list
                                        $set('columns', ['id', 'name']);
                                    }
                                }),
                            Section::make([
                                CheckboxList::make('columns')
                                    ->label('')
                                    ->options($exportColumnOptions)
                                    ->default(['id', 'name'])
                                    ->columns(3)
                                    ->required()
                                    ->disableOptionWhen(fn (string $value): bool => in_array($value, ['id', 'name']))
                                    ->in(array_keys($exportColumnOptions)),
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
                    Step::make(__('general.columns'))
                        ->schema([
                            Section::make([

                            ])
                                ->description(__('general.add_custom_columns_description')),
                        ])
                        ->description(__('general.add_custom_columns'))
                        ->icon('heroicon-o-table-cells'),
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
                            ])
                                ->description(__('general.file_type')),
                        ])
                        ->icon('heroicon-o-cog-6-tooth'),
                ])
                ->action(function (Collection $records, array $data, $table) {
                    try {
                        $data['records'] = $records->filter(fn ($record) => $record->status !== 'locked');

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
                    ->visible(Gate::check('bulkDelete', Item::class)),
                RestoreBulkAction::make()
                    ->visible(Gate::check('bulkRestore', Item::class)),
                BulkAction::make('setWillBeBroughtToNextEvent')
                    ->label(__('general.will_be_brought_along'))
                    ->action(function (Collection $records) {
                        $records->each->update(['will_be_brought_to_next_event' => true]);

                        Notification::make()
                            ->body(__('general.saved'))
                            ->success()
                            ->send();
                    })
                    ->icon('heroicon-o-check-circle'),
                BulkAction::make('unsetWillBeBroughtToNextEvent')
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
        ];
    }

    public static function getGroups(): array
    {
        return [
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
                    if (! empty($record->connected_operation_site)) {
                        return ucfirst($record->connected_operation_site->name)." ({$record->connected_department->name})";
                    }

                    return __('general.no_operation_site');
                })
                ->collapsible(),
            Group::make('connected_sub_category.name')
                ->label(__('general.sub_category'))
                ->getTitleFromRecordUsing(function (Item $record): string {
                    if (! empty($record->connected_sub_category)) {
                        return ucfirst($record->connected_sub_category->name)." ({$record->connected_department->name})";
                    }

                    return __('general.no_category');
                })
                ->collapsible(),
        ];
    }
}

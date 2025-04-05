<?php

namespace App\Filament\App\Resources;

use Carbon\Carbon;
use Filament\Forms;
use App\Models\Item;
use Filament\Tables;
use App\Models\Storage;
use Filament\Forms\Get;
use App\Models\BaseUnit;
use Filament\Forms\Form;
use App\Models\Department;
use Filament\Tables\Table;
use App\Exports\StandartExport;
use Filament\Resources\Resource;
use Filament\Forms\Components\Tabs;
use Filament\Tables\Grouping\Group;
use Illuminate\Support\Facades\Log;
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
use Filament\Forms\Components\DateTimePicker;
use App\Filament\App\Resources\ItemResource\Pages;
use Filament\Tables\Actions\Action as TableAction;
use Archilex\ToggleIconColumn\Columns\ToggleIconColumn;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

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
        return ['name', 'shortname', 'serialnumber', 'url'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('general.name') => $record->name,
            __('general.shortname') => $record->shortname,
            __('general.department') => $record->department_->name,
            __('general.created_at') => $record->created_at,
        ];
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
                                                    ->maxLength(64),
                                                TextInput::make('shortname')
                                                    ->unique(ignoreRecord: true)
                                                    ->hint(__('general.unique_name'))
                                                    ->label(__('general.shortname')),
                                                Select::make('department')
                                                    ->label(__('general.department'))
                                                    ->required()
                                                    ->exists('departments', 'id')
                                                    ->options(function (): array {
                                                        $options = Auth::user()->can('can-choose-all-departments')
                                                            ? Department::withoutTrashed()->pluck('name', 'id')->toArray()
                                                            : Auth::user()->departments()->withoutTrashed()->pluck('name', 'department')->toArray();

                                                        return $options;
                                                    })
                                                    ->default(function () {
                                                        $options = Auth::user()->can('can-choose-all-departments')
                                                            ? Department::withoutTrashed()->pluck('id')->toArray()
                                                            : Auth::user()->departments()->withoutTrashed()->pluck('department')->toArray();

                                                        return count($options) === 1 ? $options[0] : null;
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
                                                ->exists('base_units', 'id'),
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
                                        ]),
                                    ]),
                                Tabs\Tab::make(__('general.storage'))
                                    ->icon('heroicon-o-building-storefront')
                                    ->schema([
                                        Select::make('storage')
                                            ->label(__('general.storage'))
                                            ->options(function (): array {
                                                $options = Storage::all(['id', 'name'])->pluck('name', 'id')->toArray();

                                                return $options;
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
                                            ->reorderable()
                                            ->panelLayout('grid')
                                            ->appendFiles()
                                            ->openable()
                                            ->downloadable()
                                            ->previewable()
                                            ->visibility('private')
                                            ->responsiveImages(),
                                    ]),
                                Tabs\Tab::make(__('general.qr_code'))
                                    ->icon('heroicon-o-qr-code')
                                    ->schema([
                                        Tabs::make('Tabs')
                                            ->tabs([
                                                Tabs\Tab::make('Tab 1')
                                                    ->schema([
                                                        // ...
                                                    ]),
                                                Tabs\Tab::make('Tab 2')
                                                    ->schema([
                                                        // ...
                                                    ]),
                                                Tabs\Tab::make('Tab 3')
                                                    ->schema([
                                                        // ...
                                                    ]),
                                            ])
                                    ])
                                    ->visible(false),
                            ])

                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        $export_type_options = ['standart' => __('general.standart')];

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
                    ->label(__('general.name')),
                TextColumn::make('shortname')
                    ->sortable()
                    ->searchable()
                    ->label(__('general.shortname'))
                    ->toggleable(true, true),
                TextColumn::make('department_.name')
                    ->sortable()
                    ->searchable()
                    ->label(__('general.department'))
                    ->toggleable(),
                TextColumn::make('serialnumber')
                    ->sortable()
                    ->searchable()
                    ->label(__('general.serialnumber'))
                    ->toggleable(true, true),
                ToggleIconColumn::make('sorted_out')
                    ->sortable()
                    ->toggleable(true, true)
                    ->label(__('general.sorted_out'))
                    ->disabled(true),
                TextColumn::make('created_at')
                    ->label(__('general.order_date'))
                    ->date()
                    ->toggleable()
                    ->sortable(),
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
                TernaryFilter::make('sorted_out')
                    ->nullable()
                    ->label(__('general.sorted_out')),
            ])
            ->filtersFormColumns(3)
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\ViewAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(Gate::check('bulkDelete', Item::class)),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(Gate::check('bulkRestore', Item::class)),
                ]),
            ])
            ->groups([
                Group::make('name')
                    ->label(__('general.name'))
                    ->collapsible(),
                Group::make('department.name')
                    ->label(__('general.department'))
                    ->collapsible(),
                Group::make('created_at')
                    ->label(__('general.order_date'))
                    ->date()
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
        ];
    }
}

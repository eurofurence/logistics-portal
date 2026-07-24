<?php

namespace App\Filament\App\Resources\Storages;

use App\Filament\App\Resources\Storages\Pages\CreateStorage;
use App\Filament\App\Resources\Storages\Pages\EditStorage;
use App\Filament\App\Resources\Storages\Pages\ListStorages;
use App\Filament\App\Resources\Storages\Pages\ViewStorage;
use App\Models\Department;
use App\Models\Storage;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Parfaitementweb\FilamentCountryField\Forms\Components\Country;

class StorageResource extends Resource
{
    protected static ?string $model = Storage::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.inventory');

        return static::$navigationGroup;
    }

    public static function getNavigationLabel(): string
    {
        return __('general.storage');
    }

    public static function getModelLabel(): string
    {
        return __('general.storage');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.storages');
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
            __('general.name') => $record->name,
        ];
    }

    public static function getRoutePrefix(Panel $panel): string
    {
        return 'storage';
    }

    /**
     * Checks if the current request route corresponds to the storage view page.
     *
     * This static method determines whether the current route name matches
     * the specific route used for viewing an storage in the Filament application.
     *
     * @return bool Returns true if the current route is the storage view page, false otherwise.
     */
    public static function isView(): bool
    {
        return request()->route()->getName() === 'filament.app.resources.storages.view';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('General')
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 2,
                                    'lg' => 2,
                                ])
                                    ->schema([
                                        TextInput::make('name')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(64)
                                            ->label(__('general.name')),
                                        Select::make('type')
                                            ->options([
                                                1 => __('general.global'),
                                                2 => __('general.department'),
                                            ])
                                            ->default(2)
                                            ->disableOptionWhen(
                                                function (string $value): bool {
                                                    return $value == 1 && ! Auth::user()->can('can-create-global-storages');
                                                }
                                            )
                                            ->label(__('general.type'))
                                            ->required(),
                                        Select::make('managing_department')
                                            ->exists('departments', 'id')
                                            ->options(function (): array {
                                                if (self::isView()) {
                                                    return Department::query()->pluck('name', 'id')->toArray();
                                                }

                                                if (Auth::user()->can('can-create-storages-for-all-departments')) {
                                                    return Department::query()->pluck('name', 'id')->toArray();
                                                } else {
                                                    return Auth::user()->getDepartmentsWithPermission('create-Storage')->pluck('name', 'id')->toArray();
                                                }
                                            })
                                            ->searchable()
                                            ->required(true)
                                            ->label(__('general.department')),
                                        Fieldset::make('address_fieldset')
                                            ->schema([
                                                Country::make('country')
                                                    ->label(__('general.country')),
                                                TextInput::make('street')
                                                    ->maxLength(128)
                                                    ->label(__('general.street')),
                                                TextInput::make('city')
                                                    ->maxLength(128)
                                                    ->label(__('general.city')),
                                                TextInput::make('post_code')
                                                    ->maxLength(64)
                                                    ->label(__('general.post_code')),
                                            ])
                                            ->columns([
                                                'default' => 1,
                                                'sm' => 1,
                                                'md' => 2,
                                                'lg' => 2,
                                            ])
                                            ->label(__('general.address')),
                                        Fieldset::make('miscellaneous')
                                            ->schema([
                                                Textarea::make('comment')
                                                    ->nullable()
                                                    ->maxLength(10000)
                                                    ->label(__('general.comment')),
                                                Textarea::make('contact_details')
                                                    ->nullable()
                                                    ->maxLength(10000)
                                                    ->label(__('general.contact_details')),
                                            ])
                                            ->label(__('general.miscellaneous')),
                                    ]),
                            ])
                            ->label(__('general.general'))
                            ->icon('heroicon-o-bars-3-center-left'),
                        Tab::make('Access')
                            ->schema([
                                Repeater::make('departments')

                                    ->relationship('departments')
                                    ->simple(
                                        Select::make('department')
                                            ->options(Department::query()->pluck('name', 'id'))
                                    )
                                    ->defaultItems(1)
                                    ->disabled(),
                            ])
                            ->label(__('general.access'))
                            ->icon('heroicon-o-key')
                            ->visible(false),
                    ])
                    ->columnSpanFull()
                    ->persistTab(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable()
                    ->label(__('general.id'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('type')
                    ->icon(fn (string $state): string => match ($state) {
                        '0' => 'heroicon-o-exclamation-triangle',
                        '1' => 'heroicon-o-globe-alt',
                        '2' => 'heroicon-o-user-group',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '0' => __('general.undefined'),
                        '1' => __('general.global'),
                        '2' => __('general.department'),
                    })
                    ->label(__('general.type'))
                    ->sortable(),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->label(__('general.name')),
                TextColumn::make('country')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('general.country')),
                TextColumn::make('street')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('general.street')),
                TextColumn::make('city')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('general.city')),
                TextColumn::make('post_code')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('general.post_code')),
            ])
            ->filters([
                TrashedFilter::make()
                    ->visible(fn (): bool => Gate::allows('restore', Storage::class) || Gate::allows('forceDelete', Storage::class) || Gate::allows('bulkForceDelete', Storage::class) || Gate::allows('bulkRestore', Storage::class)),
                SelectFilter::make('managing_department')
                    ->options(function (): array {
                        if (Auth::user()->can('can-see-all-storages')) {
                            return Department::query()->pluck('name', 'id')->toArray();
                        } else {
                            return Auth::user()->getDepartmentsWithPermission('view-Storage')->pluck('name', 'id')->toArray();
                        }
                    })
                    ->label(__('general.managing_department')),
                SelectFilter::make('type')
                    ->options([
                        1 => __('general.global'),
                        2 => __('general.department'),
                    ])
                    ->label(__('general.type')),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label(__('general.created_from'))
                            ->placeholder(fn ($state): string => 'Dec 18, '.now()->subYear()->format('Y')),
                        DatePicker::make('created_until')
                            ->label(__('general.created_until'))
                            ->placeholder(fn ($state): string => now()->format('M d, Y')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = __('general.created_from').' '.Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = __('general.created_until').' '.Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
                        ->modalHeading(function ($record): string {
                            return __('general.delete').': '.$record->name;
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(Gate::check('bulkDelete', Storage::class)),
                    RestoreBulkAction::make()
                        ->visible(Gate::check('bulkRestore', Storage::class)),
                ]),
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
            'index' => ListStorages::route('/'),
            'create' => CreateStorage::route('/create'),
            'edit' => EditStorage::route('/{record}/edit'),
            'view' => ViewStorage::route('/{record}'),
        ];
    }
}

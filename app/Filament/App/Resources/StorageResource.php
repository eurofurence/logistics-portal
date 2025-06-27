<?php

namespace App\Filament\App\Resources;

use Filament\Tables;
use App\Models\Storage;
use Filament\Forms\Form;
use App\Models\Department;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\App\Resources\StorageResource\Pages;
use Parfaitementweb\FilamentCountryField\Forms\Components\Country;

class StorageResource extends Resource
{
    protected static ?string $model = Storage::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

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

    public static function getRoutePrefix(): string
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('General')
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
                                                1 => __('general.general'),
                                                2 => __('general.department'),
                                            ])
                                            ->disableOptionWhen(
                                                function (string $value): bool {
                                                    return $value == 1 && true; //True is a placeholder for the permission check
                                                }
                                            )
                                            ->label(__('general.type'))
                                            ->required(),
                                        Select::make('managing_department')
                                            ->relationship('managing_department', 'name')
                                            ->exists('departments', 'id')
                                            ->options(function (): array {
                                                if (self::isView()) {
                                                    return Department::all()->pluck('name', 'id')->toArray();
                                                }

                                                if (Auth::user()->can('can-create-storages-for-all-departments')) {
                                                    return Department::all()->pluck('name', 'id')->toArray();
                                                } else {
                                                    return Auth::user()->getDepartmentsWithPermission('create-Storage')->pluck('name', 'id')->toArray();
                                                }
                                            })
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
                                            ->label(__('general.miscellaneous'))
                                    ])
                            ])
                            ->label(__('general.general'))
                            ->icon('heroicon-o-bars-3-center-left'),
                        Tabs\Tab::make('Access')
                            ->schema([
                                Repeater::make('departments')

                                    ->relationship('departments')
                                    ->simple(
                                        Select::make('department')
                                            ->options(Department::all()->pluck('name', 'id'))
                                    )
                                    ->defaultItems(1)
                                    ->disabled()
                            ])
                            ->label(__('general.access'))
                            ->icon('heroicon-o-key')
                            ->disabled(false),
                    ])
                    ->columnSpanFull()
                    ->persistTab()

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
                    ->toggleable(),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->label(__('general.name')),
                TextColumn::make('country')
                    ->sortable()
                    ->toggleable()
                    ->label(__('general.country')),
                TextColumn::make('street')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('general.street')),
                TextColumn::make('city')
                    ->sortable()
                    ->toggleable()
                    ->label(__('general.city')),
                TextColumn::make('post_code')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('general.post_code')),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->visible(fn(Storage $record): bool => Gate::allows('restore', $record) || Gate::allows('forceDelete', $record) || Gate::allows('bulkForceDelete', $record) || Gate::allows('bulkRestore', $record)),
            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(Gate::check('bulkDelete', Storage::class)),
                    Tables\Actions\RestoreBulkAction::make()
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
            'index' => Pages\ListStorages::route('/'),
            'create' => Pages\CreateStorage::route('/create'),
            'edit' => Pages\EditStorage::route('/{record}/edit'),
            'view' => Pages\ViewStorage::route('/{record}'),
        ];
    }
}

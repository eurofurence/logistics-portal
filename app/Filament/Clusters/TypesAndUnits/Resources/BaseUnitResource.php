<?php

namespace App\Filament\Clusters\TypesAndUnits\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\SubUnit;
use App\Models\BaseUnit;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Gate;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Clusters\TypesAndUnits;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Clusters\TypesAndUnits\Resources\BaseUnitResource\Pages;
use App\Filament\Clusters\TypesAndUnits\Resources\BaseUnitResource\RelationManagers;

class BaseUnitResource extends Resource
{
    protected static ?string $model = BaseUnit::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';

    protected static ?string $cluster = TypesAndUnits::class;

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.inventory');

        return static::$navigationGroup;
    }

    public static function getNavigationLabel(): string
    {
        return __('general.base_units');
    }

    public static function getModelLabel(): string
    {
        return __('general.base_unit');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.base_units');
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
            __('general.sub_unit') => $record->subUnit->name
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(64)
                            ->label(__('general.name')),
                        Select::make('sub_unit')
                            ->required()
                            ->options(
                                SubUnit::all(['id', 'name'])->pluck('name', 'id')
                            )
                            ->exists(table: SubUnit::class, column: 'id')
                            ->label(__('general.sub_unit'))
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->toggleable()
                    ->label(__('general.id')),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(__('general.name')),
                TextColumn::make('subUnit.name')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->label(__('general.sub_unit'))
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->visible(fn (BaseUnit $record): bool => Gate::allows('restore', $record) || Gate::allows('forceDelete', $record) || Gate::allows('bulkForceDelete', $record) || Gate::allows('bulkRestore', $record)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListBaseUnits::route('/'),
            'create' => Pages\CreateBaseUnit::route('/create'),
            'edit' => Pages\EditBaseUnit::route('/{record}/edit'),
        ];
    }
}

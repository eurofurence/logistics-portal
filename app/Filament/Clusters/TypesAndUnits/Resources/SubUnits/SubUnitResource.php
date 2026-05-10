<?php

namespace App\Filament\Clusters\TypesAndUnits\Resources\SubUnits;

use App\Filament\Clusters\TypesAndUnits\Resources\SubUnits\Pages\CreateSubUnit;
use App\Filament\Clusters\TypesAndUnits\Resources\SubUnits\Pages\EditSubUnit;
use App\Filament\Clusters\TypesAndUnits\Resources\SubUnits\Pages\ListSubUnits;
use App\Filament\Clusters\TypesAndUnits\TypesAndUnitsCluster;
use App\Models\SubUnit;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class SubUnitResource extends Resource
{
    protected static ?string $model = SubUnit::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?string $cluster = TypesAndUnitsCluster::class;

    public static function getNavigationLabel(): string
    {
        return __('general.sub_units');
    }

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.inventory');

        return static::$navigationGroup;
    }

    public static function getModelLabel(): string
    {
        return __('general.sub_unit');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.sub_units');
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
            __('general.value') => $record->value,
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(64),
                        TextInput::make('value')
                            ->required(),
                    ]),
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
                TextColumn::make('value')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->label(__('general.value')),
            ])
            ->filters([
                TrashedFilter::make()
                    ->visible(fn (): bool => Gate::allows('restore', SubUnit::class) || Gate::allows('forceDelete', SubUnit::class) || Gate::allows('bulkForceDelete', SubUnit::class) || Gate::allows('bulkRestore', SubUnit::class)),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListSubUnits::route('/'),
            'create' => CreateSubUnit::route('/create'),
            'edit' => EditSubUnit::route('/{record}/edit'),
        ];
    }
}

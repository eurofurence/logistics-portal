<?php

namespace App\Filament\Clusters\TypesAndUnits\Resources\ContainerTypes;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\TypesAndUnits\Resources\ContainerTypes\Pages\ListContainerTypes;
use App\Filament\Clusters\TypesAndUnits\Resources\ContainerTypes\Pages\CreateContainerType;
use App\Filament\Clusters\TypesAndUnits\Resources\ContainerTypes\Pages\EditContainerType;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\ContainerType;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Clusters\TypesAndUnits\TypesAndUnitsCluster;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Clusters\TypesAndUnits\Resources\ContainerTypeResource\Pages;

class ContainerTypeResource extends Resource
{
    protected static ?string $model = ContainerType::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-viewfinder-circle';

    protected static ?string $cluster = TypesAndUnitsCluster::class;

    public static function getNavigationLabel(): string
    {
        return __('general.container_types');
    }

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.inventory');

        return static::$navigationGroup;
    }

    public static function getModelLabel(): string
    {
        return __('general.container_type');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.container_types');
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
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
                            ->maxLength(64)
                            ->label(__('general.name')),
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
            ])
            ->filters([
                TrashedFilter::make()
                    ->visible(fn (ContainerType $record): bool => Gate::allows('restore', $record) || Gate::allows('forceDelete', $record) || Gate::allows('bulkForceDelete', $record) || Gate::allows('bulkRestore', $record)),
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
            'index' => ListContainerTypes::route('/'),
            'create' => CreateContainerType::route('/create'),
            'edit' => EditContainerType::route('/{record}/edit'),
        ];
    }
}

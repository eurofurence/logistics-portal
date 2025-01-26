<?php

namespace App\Filament\Clusters\TypesAndUnits\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\SubUnit;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Gate;
//use App\Filament\App\Clusters\TypesAndUnits;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Clusters\TypesAndUnits;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\App\Resources\SubUnitResource\RelationManagers;
use App\Filament\Clusters\TypesAndUnits\Resources\SubUnitResource\Pages;

class SubUnitResource extends Resource
{
    protected static ?string $model = SubUnit::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $cluster = TypesAndUnits::class;

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
            __('general.value') => $record->value
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
                            ->maxLength(64),
                        TextInput::make('value')
                            ->required()
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->toggleable()
                    ->label(__('general.id')),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(__('general.name')),
                Tables\Columns\TextColumn::make('value')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->label(__('general.value'))
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->visible(fn (SubUnit $record): bool => Gate::allows('restore', $record) || Gate::allows('forceDelete', $record) || Gate::allows('bulkForceDelete', $record) || Gate::allows('bulkRestore', $record)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
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
            'index' => Pages\ListSubUnits::route('/'),
            'create' => Pages\CreateSubUnit::route('/create'),
            'edit' => Pages\EditSubUnit::route('/{record}/edit'),
        ];
    }
}

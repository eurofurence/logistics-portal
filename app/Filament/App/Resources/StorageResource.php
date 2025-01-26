<?php

namespace App\Filament\App\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Storage;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Gate;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\App\Resources\StorageResource\Pages;
use App\Filament\App\Resources\StorageResource\RelationManagers;
use Filament\Forms\Components\Section;
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                ->schema([
                    TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(64)
                    ->label(__('general.name')),
                Textarea::make('contact_details')
                    ->nullable()
                    ->maxLength(10000)
                    ->label(__('general.contact_details')),
                Country::make('country')
                    ->required()
                    ->label(__('general.country')),
                TextInput::make('street')
                    ->required()
                    ->maxLength(128)
                    ->label(__('general.street')),
                TextInput::make('city')
                    ->required()
                    ->maxLength(128)
                    ->label(__('general.city')),
                TextInput::make('post_code')
                    ->required()
                    ->maxLength(64)
                    ->label(__('general.post_code')),
                Textarea::make('comment')
                    ->nullable()
                    ->maxLength(10000)
                    ->label(__('general.comment'))
                ])
                ->columns(2)
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
                    ->visible(fn (Storage $record): bool => Gate::allows('restore', $record) || Gate::allows('forceDelete', $record) || Gate::allows('bulkForceDelete', $record) || Gate::allows('bulkRestore', $record)),
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
            'index' => Pages\ListStorages::route('/'),
            'create' => Pages\CreateStorage::route('/create'),
            'edit' => Pages\EditStorage::route('/{record}/edit'),
        ];
    }
}

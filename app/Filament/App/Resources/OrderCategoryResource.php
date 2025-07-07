<?php

namespace App\Filament\App\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Department;
use Filament\Tables\Table;
use App\Models\OrderCategory;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Gate;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\App\Resources\OrderCategoryResource\Pages;

class OrderCategoryResource extends Resource
{
    protected static ?string $model = OrderCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.orders');

        return static::$navigationGroup;
    }

    public static function getNavigationLabel(): string
    {
        return __('general.categories');
    }

    public static function getModelLabel(): string
    {
        return __('general.category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.categories');
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('general.informations'))
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Textarea::make('description')
                            ->nullable()
                            ->maxLength(1000)
                            ->label(__('general.description')),

                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('general.id'))
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('general.name'))
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label(__('general.description'))
                    ->color('gray')
                    ->limit(30)
                    ->toggleable(true, true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->visible(fn(OrderCategory $record): bool => Gate::allows('restore', $record) || Gate::allows('forceDelete', $record) || Gate::allows('bulkForceDelete', $record) || Gate::allows('bulkRestore', $record)),
            ])
            ->actions([
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading(function ($record): string {
                        return __('general.delete') . ': ' . $record->name;
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn(OrderCategory $record): bool => Gate::allows('bulk-delete', $record)),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn(OrderCategory $record): bool => Gate::allows('bulk-restore-OrderCategory', $record)),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn(OrderCategory $record): bool => Gate::allows('bulk-force-delete-OrderCategory', $record))
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrderCategories::route('/'),
            'create' => Pages\CreateOrderCategory::route('/create'),
            'edit' => Pages\EditOrderCategory::route('/{record}/edit'),
            'view' => Pages\ViewOrderCategory::route('/{record}'),
        ];
    }
}

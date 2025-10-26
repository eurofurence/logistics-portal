<?php

namespace App\Filament\App\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use App\Filament\App\Resources\OrderCategoryResource\Pages\ListOrderCategories;
use App\Filament\App\Resources\OrderCategoryResource\Pages\CreateOrderCategory;
use App\Filament\App\Resources\OrderCategoryResource\Pages\EditOrderCategory;
use App\Filament\App\Resources\OrderCategoryResource\Pages\ViewOrderCategory;
use Filament\Tables;
use App\Models\Department;
use Filament\Tables\Table;
use App\Models\OrderCategory;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Gate;
use Filament\Forms\Components\Select;
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

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-table-cells';

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                TrashedFilter::make()
                    ->visible(fn(OrderCategory $record): bool => Gate::allows('restore', $record) || Gate::allows('forceDelete', $record) || Gate::allows('bulkForceDelete', $record) || Gate::allows('bulkRestore', $record)),
            ])
            ->recordActions([
                RestoreAction::make(),
                ForceDeleteAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->modalHeading(function ($record): string {
                        return __('general.delete') . ': ' . $record->name;
                    }),
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn(OrderCategory $record): bool => Gate::allows('bulk-delete', $record)),
                    RestoreBulkAction::make()
                        ->visible(fn(OrderCategory $record): bool => Gate::allows('bulk-restore-OrderCategory', $record)),
                    ForceDeleteBulkAction::make()
                        ->visible(fn(OrderCategory $record): bool => Gate::allows('bulk-force-delete-OrderCategory', $record))
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrderCategories::route('/'),
            'create' => CreateOrderCategory::route('/create'),
            'edit' => EditOrderCategory::route('/{record}/edit'),
            'view' => ViewOrderCategory::route('/{record}'),
        ];
    }
}

<?php

namespace App\Filament\App\Resources\OrderCategories\Tables;

use App\Models\OrderCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class OrderCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::getColumns())
            ->filters(self::getFilters())
            ->recordActions(self::getRecordActions())
            ->toolbarActions(self::getToolbarActions());
    }

    public static function getColumns(): array
    {
        return [
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
        ];
    }

    public static function getFilters(): array
    {
        return [
            TrashedFilter::make()
                ->visible(fn (): bool => Gate::allows('restore', OrderCategory::class) || Gate::allows('forceDelete', OrderCategory::class) || Gate::allows('bulkForceDelete', OrderCategory::class) || Gate::allows('bulkRestore', OrderCategory::class)),
        ];
    }

    public static function getRecordActions(): array
    {
        return [
            RestoreAction::make(),
            ForceDeleteAction::make(),
            EditAction::make(),
            DeleteAction::make()
                ->modalHeading(function ($record): string {
                    return __('general.delete').': '.$record->name;
                }),
            ViewAction::make(),
        ];
    }

    public static function getToolbarActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->visible(fn (): bool => Gate::allows('bulk-delete', OrderCategory::class)),
                RestoreBulkAction::make()
                    ->visible(fn (): bool => Gate::allows('bulk-restore-OrderCategory', OrderCategory::class)),
                ForceDeleteBulkAction::make()
                    ->visible(fn (): bool => Gate::allows('bulk-force-delete-OrderCategory', OrderCategory::class)),
            ]),
        ];
    }
}

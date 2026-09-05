<?php

namespace App\Filament\Admin\Resources\TestModels\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TestModelsTable
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
            TextColumn::make('id'),
            TextColumn::make('data1'),
            TextColumn::make('data2'),
            TextColumn::make('data3'),
            TextColumn::make('data4'),
            TextColumn::make('data5'),
            TextColumn::make('data6'),
            TextColumn::make('data7'),
            TextColumn::make('created_at'),
            TextColumn::make('updated_at'),
        ];
    }

    public static function getFilters(): array
    {
        return [

        ];
    }

    public static function getRecordActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public static function getToolbarActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ];
    }
}

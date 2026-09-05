<?php

namespace App\Filament\Admin\Resources\Whitelists\Tables;

use App\Models\Whitelist;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class WhitelistsTable
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
            TextColumn::make('id')->searchable(),
            TextColumn::make('email')->searchable(),
            TextColumn::make('user.name')->searchable(),
        ];
    }

    public static function getFilters(): array
    {
        return [
            //
        ];
    }

    public static function getRecordActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->modalHeading(function ($record): string {
                    return __('general.delete').': '.$record->email;
                }),
        ];
    }

    public static function getToolbarActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->visible(Gate::check('bulkDelete', Whitelist::class)),
                RestoreBulkAction::make()
                    ->visible(Gate::check('bulkRestore', Whitelist::class)),
            ]),
        ];
    }
}

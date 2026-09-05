<?php

namespace App\Filament\Admin\Resources\IdpRankSyncs\Tables;

use App\Models\IdpRankSync;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class IdpRankSyncsTable
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
                ->sortable()
                ->toggleable(true),
            TextColumn::make('name')
                ->label(__('general.name'))
                ->sortable()
                ->toggleable(),
            TextColumn::make('role.name')
                ->label(__('general.local_role')),
            TextColumn::make('idp_group')
                ->label(__('general.idp_group')),
        ];
    }

    public static function getFilters(): array
    {
        return [
            TrashedFilter::make(),
        ];
    }

    public static function getRecordActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->modalHeading(function ($record): string {
                    return __('general.delete').': '.$record->name;
                }),
        ];
    }

    public static function getToolbarActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->visible(Gate::check('bulkDelete', IdpRankSync::class)),
                RestoreBulkAction::make()
                    ->visible(Gate::check('bulkRestore', IdpRankSync::class)),
            ]),
        ];
    }
}

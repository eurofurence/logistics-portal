<?php

namespace App\Filament\Admin\Resources\Departments\Tables;

use App\Models\Department;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class DepartmentsTable
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
                ->searchable()
                ->toggleable(true)
                ->label(__('general.id')),
            TextColumn::make('name')
                ->searchable()
                ->sortable()
                ->label(__('general.name')),
            TextColumn::make('idp_group_id')
                ->searchable()
                ->toggleable()
                ->label(__('general.idp_group'))
                ->visible(config('app.identity_mode')),
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
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    public static function getToolbarActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->visible(Gate::check('bulkDelete', Department::class)),
                RestoreBulkAction::make()
                    ->visible(Gate::check('bulkRestore', Department::class)),
            ]),
        ];
    }
}

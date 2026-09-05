<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class UsersTable
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
                ->sortable()
                ->label(__('general.id')),
            TextColumn::make('name')
                ->searchable()
                ->sortable()
                ->label(__('general.name')),
            TextColumn::make('email')
                ->searchable()
                ->sortable()
                ->label(__('general.email')),
            TextColumn::make('email_verified_at')
                ->dateTime(timezone: 'Europe/Berlin')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->label(__('general.email_verified_at')),
            TextColumn::make('created_at')
                ->dateTime(timezone: 'Europe/Berlin')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->label(__('general.created_at')),
            TextColumn::make('updated_at')
                ->dateTime(timezone: 'Europe/Berlin')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->label(__('general.updated_at')),
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
            ViewAction::make(),
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
                    ->visible(Gate::check('bulkDelete', User::class)),
                RestoreBulkAction::make()
                    ->visible(Gate::check('bulkRestore', User::class)),
            ]),
        ];
    }
}

<?php

namespace App\Filament\App\Resources\OrderEvents\Tables;

use App\Models\OrderEvent;
use Carbon\Carbon;
use Exception;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class OrderEventsTable
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
                ->toggleable(true)
                ->sortable()
                ->searchable()
                ->label(__('general.id')),
            TextColumn::make('name')
                ->sortable()
                ->searchable()
                ->label(__('general.name')),
            TextColumn::make('order_deadline')
                ->sortable()
                ->searchable()
                ->toggleable(true)
                ->default(__('general.not_set'))
                ->label(__('general.order_deadline'))
                ->formatStateUsing(function ($state) {
                    if (! $state || $state === __('general.not_set')) {
                        return __('general.not_set');
                    }

                    try {
                        return Carbon::parse($state)->setTimezone('Europe/Berlin')->format('d.m.Y H:i');
                    } catch (Exception $e) {
                        return __('general.not_set');
                    }
                }),
        ];
    }

    public static function getFilters(): array
    {
        return [
            TrashedFilter::make()
                ->visible(fn (): bool => Gate::allows('restore', OrderEvent::class) || Gate::allows('forceDelete', OrderEvent::class) || Gate::allows('bulkForceDelete', OrderEvent::class) || Gate::allows('bulkRestore', OrderEvent::class)),
            SelectFilter::make('locked')
                ->options([
                    '0' => __('general.unlocked'),
                    '1' => __('general.locked'),
                ]),
            TernaryFilter::make('is_active')
                ->label(__('general.is_active')),
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
            RestoreAction::make(),
        ];
    }

    public static function getToolbarActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->visible(Gate::check('bulkDelete', OrderEvent::class)),
                RestoreBulkAction::make()
                    ->visible(Gate::check('bulkRestore', OrderEvent::class)),
            ]),
        ];
    }
}

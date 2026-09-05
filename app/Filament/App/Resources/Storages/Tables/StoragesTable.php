<?php

namespace App\Filament\App\Resources\Storages\Tables;

use App\Models\Department;
use App\Models\Storage;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class StoragesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::getColumns())
            ->filters(self::getFilters(), layout: FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->recordActions(self::getRecordActions())
            ->toolbarActions(self::getToolbarActions());
    }

    public static function getColumns(): array
    {
        return [
            TextColumn::make('id')
                ->sortable()
                ->searchable()
                ->label(__('general.id'))
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('type')
                ->icon(fn (string $state): string => match ($state) {
                    '0' => 'heroicon-o-exclamation-triangle',
                    '1' => 'heroicon-o-globe-alt',
                    '2' => 'heroicon-o-user-group',
                })
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    '0' => __('general.undefined'),
                    '1' => __('general.global'),
                    '2' => __('general.department'),
                })
                ->label(__('general.type'))
                ->sortable(),
            TextColumn::make('name')
                ->sortable()
                ->searchable()
                ->label(__('general.name')),
            TextColumn::make('country')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->label(__('general.country')),
            TextColumn::make('street')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->label(__('general.street')),
            TextColumn::make('city')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->label(__('general.city')),
            TextColumn::make('post_code')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->label(__('general.post_code')),
        ];
    }

    public static function getFilters(): array
    {
        return [
            TrashedFilter::make()
                ->visible(fn (): bool => Gate::allows('restore', Storage::class) || Gate::allows('forceDelete', Storage::class) || Gate::allows('bulkForceDelete', Storage::class) || Gate::allows('bulkRestore', Storage::class)),
            SelectFilter::make('managing_department')
                ->options(function (): array {
                    if (Auth::user()->can('can-see-all-storages')) {
                        return Department::query()->pluck('name', 'id')->toArray();
                    } else {
                        return Auth::user()->getDepartmentsWithPermission('view-Storage')->pluck('name', 'id')->toArray();
                    }
                })
                ->label(__('general.managing_department')),
            SelectFilter::make('type')
                ->options([
                    1 => __('general.global'),
                    2 => __('general.department'),
                ])
                ->label(__('general.type')),
            Filter::make('created_at')
                ->schema([
                    DatePicker::make('created_from')
                        ->label(__('general.created_from'))
                        ->placeholder(fn ($state): string => 'Dec 18, '.now()->subYear()->format('Y')),
                    DatePicker::make('created_until')
                        ->label(__('general.created_until'))
                        ->placeholder(fn ($state): string => now()->format('M d, Y')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['created_from'] ?? null,
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['created_until'] ?? null,
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];
                    if ($data['created_from'] ?? null) {
                        $indicators['created_from'] = __('general.created_from').' '.Carbon::parse($data['created_from'])->toFormattedDateString();
                    }
                    if ($data['created_until'] ?? null) {
                        $indicators['created_until'] = __('general.created_until').' '.Carbon::parse($data['created_until'])->toFormattedDateString();
                    }

                    return $indicators;
                }),
        ];
    }

    public static function getRecordActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->modalHeading(function ($record): string {
                        return __('general.delete').': '.$record->name;
                    }),
            ]),
        ];
    }

    public static function getToolbarActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->visible(Gate::check('bulkDelete', Storage::class)),
                RestoreBulkAction::make()
                    ->visible(Gate::check('bulkRestore', Storage::class)),
            ]),
        ];
    }
}

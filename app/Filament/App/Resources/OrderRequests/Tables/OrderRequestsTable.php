<?php

namespace App\Filament\App\Resources\OrderRequests\Tables;

use App\Models\Department;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderRequest;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class OrderRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::getColumns())
            ->filters(self::getFilters(), layout: FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->recordActions(self::getRecordActions())
            ->toolbarActions(self::getToolbarActions())
            ->groups(self::getGroups())
            ->defaultGroup('department.name');
    }

    public static function getColumns(): array
    {
        return [
            TextColumn::make('id')
                ->label(__('general.id'))
                ->toggleable()
                ->searchable()
                ->sortable(),
            TextColumn::make('title')
                ->label(__('general.title'))
                ->searchable()
                ->sortable(),
            TextColumn::make('department.name')
                ->label(__('general.department'))
                ->sortable()
                ->toggleable(),
            TextColumn::make('event.name')
                ->label(__('general.order_event'))
                ->sortable()
                ->toggleable(),
            TextColumn::make('url')
                ->label(__('general.url'))
                ->toggleable(true, true)
                ->searchable()
                ->limit(500),
            TextColumn::make('status')
                ->badge()
                ->label(__('general.status'))
                ->sortable()
                ->toggleable()
                ->color(fn (string $state): string => match ($state) {
                    '0' => 'warning',
                    '1' => 'success',
                    '2' => 'warning',
                    '3' => 'info',
                    '4' => 'checking',
                    '5' => 'danger',
                    default => 'gray',
                })
                ->icon(fn (string $state): string => match ($state) {
                    '0' => 'heroicon-o-clock',
                    '1' => 'heroicon-o-check-circle',
                    '2' => 'heroicon-o-arrow-path',
                    '3' => 'heroicon-o-bookmark',
                    '4' => 'heroicon-o-arrow-path',
                    '5' => 'heroicon-o-no-symbol',
                    default => 'heroicon-o-question-mark-circle',
                })
                ->formatStateUsing(function ($state) {
                    return match ($state) {
                        0 => __('general.open'),
                        1 => __('general.finished'),
                        2 => __('general.processing'),
                        3 => __('general.note'),
                        4 => __('general.checking'),
                        5 => __('general.rejected'),
                        default => 'Unknown Status',
                    };
                }),
            TextColumn::make('addedBy.name')
                ->label(__('general.requested_by'))
                ->sortable()
                ->toggleable(true, true),
        ];
    }

    public static function getFilters(): array
    {
        return [
            TrashedFilter::make()
                ->visible(fn (): bool => Gate::allows('restore', OrderRequest::class) || Gate::allows('forceDelete', OrderRequest::class) || Gate::allows('bulkForceDelete', OrderRequest::class) || Gate::allows('bulkRestore', OrderRequest::class)),
            Filter::make('created_at')
                ->schema([
                    DatePicker::make('created_from')
                        ->label(__('general.created_from'))
                        ->placeholder(fn ($state): string => 'Dec 18, '.now()->subYear()->format('Y')),
                    DatePicker::make('created_until')
                        ->label(__('general.created_until'))
                        ->placeholder(fn ($state): string => now()->format('M d, Y')),
                    Toggle::make('invert')
                        ->label(__('general.invert')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    $from = $data['created_from'] ?? null;
                    $until = $data['created_until'] ?? null;
                    $invert = $data['invert'] ?? false;

                    if (! $from && ! $until) {
                        return $query;
                    }

                    return $query->where(function (Builder $query) use ($from, $until, $invert) {
                        if ($invert) {
                            if ($from) {
                                $query->orWhereDate('created_at', '<', $from);
                            }
                            if ($until) {
                                $query->orWhereDate('created_at', '>', $until);
                            }
                        } else {
                            if ($from) {
                                $query->whereDate('created_at', '>=', $from);
                            }
                            if ($until) {
                                $query->whereDate('created_at', '<=', $until);
                            }
                        }
                    });
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];
                    $invertText = ($data['invert'] ?? false) ? ' ('.__('general.invert').')' : '';
                    if ($data['created_from'] ?? null) {
                        $indicators['created_from'] = __('general.created_from').' '.Carbon::parse($data['created_from'])->toFormattedDateString().$invertText;
                    }
                    if ($data['created_until'] ?? null) {
                        $indicators['created_until'] = __('general.created_until').' '.Carbon::parse($data['created_until'])->toFormattedDateString().$invertText;
                    }

                    return $indicators;
                }),
            Filter::make('url')
                ->form([
                    TextInput::make('url')
                        ->label(__('general.url')),
                    Toggle::make('invert')
                        ->label(__('general.invert')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    if (empty($data['url'])) {
                        return $query;
                    }

                    $invert = $data['invert'] ?? false;

                    return $invert
                        ? $query->where('url', 'not like', '%'.$data['url'].'%')
                        : $query->where('url', 'like', '%'.$data['url'].'%');
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];
                    if (! empty($data['url'])) {
                        $indicators['url'] = __('general.url').': '.$data['url'].(($data['invert'] ?? false) ? ' ('.__('general.invert').')' : '');
                    }

                    return $indicators;
                }),
            Filter::make('order_event_id')
                ->schema([
                    Select::make('value')
                        ->label(__('general.order_event'))
                        ->options(OrderEvent::all(['id', 'name'])->pluck('name', 'id'))
                        ->default(fn () => OrderEvent::where('is_active', true)->first()?->id),
                    Toggle::make('invert')
                        ->label(__('general.invert')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    if (empty($data['value'])) {
                        return $query;
                    }

                    return ($data['invert'] ?? false)
                        ? $query->where('order_event_id', '!=', $data['value'])
                        : $query->where('order_event_id', $data['value']);
                })
                ->indicateUsing(function (array $data): array {
                    if (empty($data['value'])) {
                        return [];
                    }
                    $indicator = __('general.order_event').': '.(OrderEvent::find($data['value'])?->name ?? $data['value']);
                    if ($data['invert'] ?? false) {
                        $indicator .= ' ('.__('general.invert').')';
                    }

                    return [$indicator];
                }),
            Filter::make('department_id')
                ->schema([
                    Select::make('values')
                        ->multiple()
                        ->label(__('general.department'))
                        ->options(function (): array {
                            return Auth::user()->can('can-see-all-orderRequests')
                                ? Department::query()->pluck('name', 'id')->toArray()
                                : Auth::user()->departmentsWithRoles()->pluck('name', 'id')->toArray();
                        }),
                    Toggle::make('invert')
                        ->label(__('general.invert')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    if (empty($data['values'])) {
                        return $query;
                    }

                    return ($data['invert'] ?? false)
                        ? $query->whereNotIn('department_id', $data['values'])
                        : $query->whereIn('department_id', $data['values']);
                })
                ->indicateUsing(function (array $data): array {
                    if (empty($data['values'])) {
                        return [];
                    }
                    $indicator = __('general.department').': '.count($data['values']);
                    if ($data['invert'] ?? false) {
                        $indicator .= ' ('.__('general.invert').')';
                    }

                    return [$indicator];
                }),
            Filter::make('status')
                ->schema([
                    Select::make('values')
                        ->multiple()
                        ->label(__('general.status'))
                        ->options([
                            0 => __('general.open'),
                            1 => __('general.finished'),
                            2 => __('general.processing'),
                            3 => __('general.note'),
                            4 => __('general.checking'),
                            5 => __('general.rejected'),
                        ]),
                    Toggle::make('invert')
                        ->label(__('general.invert')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    if (empty($data['values'])) {
                        return $query;
                    }

                    return ($data['invert'] ?? false)
                        ? $query->whereNotIn('status', $data['values'])
                        : $query->whereIn('status', $data['values']);
                })
                ->indicateUsing(function (array $data): array {
                    if (empty($data['values'])) {
                        return [];
                    }
                    $indicator = __('general.status').': '.count($data['values']);
                    if ($data['invert'] ?? false) {
                        $indicator .= ' ('.__('general.invert').')';
                    }

                    return [$indicator];
                }),
        ];
    }

    public static function getRecordActions(): array
    {
        return [
            ActionGroup::make([
                EditAction::make(),
                DeleteAction::make()
                    ->modalHeading(function ($record): string {
                        return __('general.delete').': '.$record->title;
                    }),
                RestoreAction::make(),
                ViewAction::make(),
                Action::make('set_status_single')
                    ->label(__('general.set_status'))
                    ->icon('heroicon-o-ellipsis-horizontal-circle')
                    ->action(function (Model $record, array $data): void {
                        $record->update(['status' => $data['status']]);
                    })
                    ->schema([
                        Select::make('status')
                            ->label(__('general.status'))
                            ->options([
                                0 => __('general.open'),
                                1 => __('general.finished'),
                                2 => __('general.processing'),
                                3 => __('general.note'),
                                4 => __('general.checking'),
                                5 => __('general.rejected'),
                            ])
                            ->prefixIcon('heroicon-o-ellipsis-horizontal-circle')
                            ->required(),
                    ])
                    ->visible(fn () => Auth::user()->can('can-moderate-order-request')),
                Action::make('open_linked_order_single')
                    ->label(__('general.open_linked_order'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->visible(fn (Model $record) => Order::where('order_request_id', $record->id)->count() === 1)
                    ->url(fn (Model $record) => route('filament.app.resources.orders.view', Order::where('order_request_id', $record->id)->first()->id)),
                Action::make('open_linked_order_multiple')
                    ->label(__('general.open_linked_order'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->visible(fn (Model $record) => Order::where('order_request_id', $record->id)->count() > 1)
                    ->schema([
                        Select::make('order_id')
                            ->label(__('general.order'))
                            ->options(fn (Model $record) => Order::where('order_request_id', $record->id)->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        return redirect(route('filament.app.resources.orders.view', $data['order_id']));
                    })
                    ->modalHeading(__('general.open_linked_order'))
                    ->modalSubmitActionLabel(__('general.show')),
            ]),
        ];
    }

    public static function getToolbarActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->visible(Gate::check('bulkDelete', OrderRequest::class)),
                RestoreBulkAction::make()
                    ->visible(Gate::check('bulkRestore', OrderRequest::class)),
                BulkAction::make('set_status')
                    ->label(__('general.set_status'))
                    ->action(function (Collection $records, array $data): void {
                        foreach ($records as $record) {
                            $record->update(['status' => $data['status']]);
                        }
                    })
                    ->icon('heroicon-o-ellipsis-horizontal-circle')
                    ->schema([
                        Select::make('status')
                            ->label(__('general.status'))
                            ->options([
                                0 => __('general.open'),
                                1 => __('general.finished'),
                                2 => __('general.processing'),
                                3 => __('general.note'),
                                4 => __('general.checking'),
                                5 => __('general.rejected'),
                            ])
                            ->prefixIcon('heroicon-o-ellipsis-horizontal-circle')
                            ->required(),
                    ])
                    ->visible(fn () => Auth::user()->can('can-moderate-order-request')),
            ]),
        ];
    }

    public static function getGroups(): array
    {
        return [
            Group::make('department.name')
                ->label(__('general.department'))
                ->collapsible(),
        ];
    }
}

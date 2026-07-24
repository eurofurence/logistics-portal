<?php

namespace App\Filament\App\Resources\Orders\Tables;

use App\Exports\MetroExport;
use App\Exports\OrderStandardExport;
use App\Models\Department;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\User;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class OrdersTable
{
    protected static array $export_column_options = [];

    protected static ?bool $isSuperAdmin = null;

    protected static ?bool $canChangeAmountAll = null;

    protected static ?bool $canAlwaysEdit = null;

    protected static ?bool $canSeeAllOrders = null;

    protected static array $userDepartmentsWithChangeAmount = [];

    public static function configure(Table $table): Table
    {
        $user = Auth::user();
        static::$isSuperAdmin = $user->isSuperAdmin();
        static::$canChangeAmountAll = $user->can('can-change-amount-order-table-all');
        static::$canAlwaysEdit = $user->can('can-always-edit-orders');
        static::$canSeeAllOrders = $user->can('can-see-all-orders');
        static::$userDepartmentsWithChangeAmount = $user->getDepartmentsWithPermission('can-change-amount-order-table')->pluck('id')->toArray();

        $export_type_options = ['standard' => __('general.standard')];

        static::$export_column_options = [
            'id' => __('general.id'),
            'name' => __('general.name'),
            'description' => __('general.description'),
            'delivery_provider' => __('general.delivery_provider'),
            'delivery_by' => __('general.delivery_by'),
            'tracking_number' => __('general.tracking_number'),
            'delivery_date' => __('general.delivery_date'),
            'instant_delivery' => __('general.instant_delivery'),
            'amount' => __('general.amount'),
            'price_net' => __('general.price_net'),
            'price_gross' => __('general.price_gross'),
            'tax_rate' => __('general.tax_rate'),
            'payment_method' => __('general.payment_method'),
            'currency' => __('general.currency'),
            'url' => __('general.url'),
            'contact' => __('general.contact'),
            'dangerous_good' => __('general.dangerous_good'),
            'big_size' => __('general.big_size'),
            'needs_truck' => __('general.needs_truck'),
            'ordered_at' => __('general.ordered_at'),
            'comment' => __('general.comment'),
            'status' => __('general.status'),
            'created_at' => __('general.created_at'),
            'updated_at' => __('general.updated_at'),
            'user_note' => __('general.user_note'),
            'returning_deposit' => __('general.returning_deposit').' ('.__('general.single').')',
            'article_number' => __('general.article_number'),
            'order_number' => __('general.order_number'),
            'approved_at' => __('general.approved_at'),
        ];

        if (Auth::user()->can('can-use-special-order-export')) {
            $export_type_options['metro_list'] = __('general.metro_list');
        }

        return $table
            ->columns([
                TextColumn::make('id')
                    ->searchable(isIndividual: true)
                    ->toggleable(true, true)
                    ->sortable()
                    ->label(__('general.id')),
                TextColumn::make('event.name')
                    ->toggleable(true, true)
                    ->searchable()
                    ->label(__('general.order_event')),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->label(__('general.name'))
                    ->formatStateUsing(fn (string $state) => Str::limit($state, 40, '...'))
                    ->description(function ($record): string {
                        $flags = array_filter([
                            $record->instant_delivery ? __('general.instant_delivery') : null,
                            $record->dangerous_good ? __('general.dangerous_good') : null,
                            $record->user_note ? __('general.user_note') : null,
                            $record->big_size ? __('general.big_size') : null,
                            $record->needs_truck ? __('general.needs_truck') : null,
                            $record->special_delivery ? __('general.special_delivery') : null,
                            $record->comment ? __('general.comment') : null,
                            $record->delivery_costs ? __('general.delivery_costs') : null,
                            $record->special_flag_text ?? null,
                        ]);

                        return implode(' \ ', $flags);
                    }),
                TextColumn::make('department.name')
                    ->toggleable(true, true)
                    ->searchable()
                    ->label(__('general.department')),
                TextColumn::make('status')
                    ->badge()
                    ->label(__('general.status'))
                    ->sortable()
                    ->toggleable()
                    ->color(fn (string $state): string => match ($state) {
                        'on_hold' => 'gray',
                        'checking' => 'checking',
                        'processing' => 'warning',
                        'open' => 'success',
                        'ordered' => 'info',
                        'delivered' => 'delivered',
                        'partially_received' => 'info',
                        'received' => 'received',
                        'rejected' => 'danger',
                        'locked' => 'danger',
                        'refunded' => 'danger',
                        'awaiting_approval' => 'checking',
                        'ready_for_pickup' => 'info',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'on_hold' => 'heroicon-o-clock',
                        'checking' => 'heroicon-o-arrow-path',
                        'processing' => 'heroicon-o-arrow-path',
                        'open' => 'heroicon-o-check-circle',
                        'ordered' => 'heroicon-o-shopping-cart',
                        'delivered' => 'heroicon-o-truck',
                        'partially_received' => 'heroicon-o-squares-plus',
                        'received' => 'heroicon-o-check',
                        'rejected' => 'heroicon-o-x-circle',
                        'locked' => 'heroicon-o-lock-closed',
                        'refunded' => 'heroicon-o-arrow-uturn-left',
                        'awaiting_approval' => 'heroicon-o-shield-exclamation',
                        'ready_for_pickup' => 'heroicon-o-shopping-bag'
                    })
                    ->extraAttributes(['class' => 'cursor-pointer'])
                    ->formatStateUsing(function ($state) {
                        return strtoupper(str_replace('_', ' ', $state));
                    }),
                TextInputColumn::make('amount')
                    ->label(__('general.quantity'))
                    ->toggleable()
                    ->sortable()
                    ->type('number')
                    ->rules(['numeric', 'min:1', 'max:1000000'])
                    ->disabled(function ($record) {
                        if (static::$isSuperAdmin) {
                            return false;
                        }

                        if ($record->department_id) {
                            if (! in_array($record->department_id, static::$userDepartmentsWithChangeAmount)) {
                                if (! static::$canChangeAmountAll) {
                                    return true;
                                }
                            }

                            if (static::$canSeeAllOrders) {
                                if (! in_array($record->department_id, static::$userDepartmentsWithChangeAmount)) {
                                    if (! static::$canChangeAmountAll) {
                                        return true;
                                    }
                                }
                            }
                        } else {
                            return true;
                        }

                        if (($record->status == 'open' || $record->status == 'awaiting_approval') && ! $record->event->locked) {
                            return false;
                        } else {
                            if (static::$canAlwaysEdit) {
                                return false;
                            }
                        }

                        return true;
                    }),
                TextColumn::make('price_net')
                    ->label(__('general.total').' ('.__('general.net').')')
                    ->formatStateUsing(function ($record) {
                        $calculatedPrice = 0;
                        if ($record->price_net) {
                            $calculatedPrice += (float) $record->price_net * (float) $record->amount;
                        }

                        if ($record->discount_net > 0) {
                            $calculatedPrice -= (float) $record->discount_net;
                        }

                        $priceFormatted = number_format($calculatedPrice, 2, ',', '.');

                        $symbol = match ($record->currency) {
                            'EUR' => '€',
                            'USD' => '$',
                            default => '€',
                        };

                        return $priceFormatted.' '.$symbol;
                    })
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('price_gross')
                    ->label(__('general.total').' ('.__('general.gross').')')
                    ->formatStateUsing(function ($record) {
                        $calculatedPrice = 0;
                        if ($record->price_gross) {
                            $calculatedPrice += (float) $record->price_gross * (float) $record->amount;
                        }

                        if ($record->discount_net > 0) {
                            $calculatedPrice -= (float) $record->discount_net * (1 + ((float) $record->tax_rate / 100));
                        }

                        $priceFormatted = number_format($calculatedPrice, 2, ',', '.');

                        $symbol = match ($record->currency) {
                            'EUR' => '€',
                            'USD' => '$',
                            default => '€',
                        };

                        return $priceFormatted.' '.$symbol;
                    })
                    ->toggleable(true, true)
                    ->sortable(),
                TextColumn::make('returning_deposit')
                    ->label(__('general.single').' ('.__('general.returning_deposit').')')
                    ->formatStateUsing(function ($record) {
                        $priceFormatted = number_format($record->returning_deposit, 2, ',', '.');

                        $symbol = match ($record->currency) {
                            'EUR' => '€',
                            'USD' => '$',
                            default => '€',
                        };

                        return $priceFormatted.' '.$symbol;
                    })
                    ->toggleable(true, true)
                    ->sortable(),
                TextColumn::make('returning_deposit')
                    ->label(__('general.total').' ('.__('general.returning_deposit').')')
                    ->formatStateUsing(function ($record) {
                        $calculatedPrice = 0;
                        if ($record->price_gross) {
                            $calculatedPrice += (float) $record->returning_deposit * (float) $record->amount;
                        }

                        $priceFormatted = number_format($calculatedPrice, 2, ',', '.');

                        $symbol = match ($record->currency) {
                            'EUR' => '€',
                            'USD' => '$',
                            default => '€',
                        };

                        return $priceFormatted.' '.$symbol;
                    })
                    ->toggleable(true, true)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('general.order_date'))
                    ->date()
                    ->toggleable()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make()
                    ->visible(fn (): bool => Gate::allows('restore', Order::class) || Gate::allows('forceDelete', Order::class) || Gate::allows('bulkForceDelete', Order::class) || Gate::allows('bulkRestore', Order::class)),
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
                Filter::make('order_event_id')
                    ->schema([
                        Select::make('value')
                            ->label(__('general.order_event'))
                            ->options(OrderEvent::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->default(function () {
                                return Cache::remember('active_order_event_id', 600, function () {
                                    return OrderEvent::where('is_active', true)->first()?->id;
                                });
                            }),
                        Toggle::make('invert')
                            ->label(__('general.invert')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        if ($data['invert'] ?? false) {
                            return $query->where('order_event_id', '!=', $data['value']);
                        }

                        return $query->where('order_event_id', $data['value']);
                    })
                    ->indicateUsing(function (array $data): array {
                        if (empty($data['value'])) {
                            return [];
                        }

                        $eventName = Cache::remember("order_event_{$data['value']}_name", 600, function () use ($data) {
                            return OrderEvent::find($data['value'])?->name;
                        });

                        $indicator = __('general.order_event').': '.($eventName ?? $data['value']);

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
                            ->searchable()
                            ->preload()
                            ->options(function (): array {
                                if (Auth::user()->can('can-see-all-orders')) {
                                    return Department::query()->pluck('name', 'id')->toArray();
                                } else {
                                    return Auth::user()->getDepartmentsWithPermission('view-Order')->pluck('name', 'department_id')->toArray();
                                }
                            }),
                        Toggle::make('invert')
                            ->label(__('general.invert')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        if ($data['invert'] ?? false) {
                            return $query->whereNotIn('department_id', $data['values']);
                        }

                        return $query->whereIn('department_id', $data['values']);
                    })
                    ->indicateUsing(function (array $data): array {
                        if (empty($data['values'])) {
                            return [];
                        }

                        $names = Cache::remember('dept_names_'.implode('_', $data['values']), 600, function () use ($data) {
                            return Department::whereIn('id', $data['values'])->pluck('name')->implode(', ');
                        });

                        $indicator = __('general.department').': '.$names;

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
                                'on_hold' => __('general.on_hold'),
                                'checking' => __('general.checking'),
                                'processing' => __('general.processing'),
                                'open' => __('general.open'),
                                'ordered' => __('general.ordered'),
                                'delivered' => __('general.delivered'),
                                'partially_received' => __('general.partially_received'),
                                'received' => __('general.received'),
                                'rejected' => __('general.rejected'),
                                'locked' => __('general.locked'),
                                'refunded' => __('general.refunded'),
                                'awaiting_approval' => __('general.awaiting_approval'),
                                'ready_for_pickup' => __('general.ready_for_pickup'),
                            ]),
                        Toggle::make('invert')
                            ->label(__('general.invert')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        if ($data['invert'] ?? false) {
                            return $query->whereNotIn('status', $data['values']);
                        }

                        return $query->whereIn('status', $data['values']);
                    })
                    ->indicateUsing(function (array $data): array {
                        if (empty($data['values'])) {
                            return [];
                        }

                        $options = [
                            'on_hold' => __('general.on_hold'),
                            'checking' => __('general.checking'),
                            'processing' => __('general.processing'),
                            'open' => __('general.open'),
                            'ordered' => __('general.ordered'),
                            'delivered' => __('general.delivered'),
                            'partially_received' => __('general.partially_received'),
                            'received' => __('general.received'),
                            'rejected' => __('general.rejected'),
                            'locked' => __('general.locked'),
                            'refunded' => __('general.refunded'),
                            'awaiting_approval' => __('general.awaiting_approval'),
                            'ready_for_pickup' => __('general.ready_for_pickup'),
                        ];

                        $names = collect($data['values'])->map(fn ($value) => $options[$value] ?? $value)->implode(', ');

                        $indicator = __('general.status').': '.$names;

                        if ($data['invert'] ?? false) {
                            $indicator .= ' ('.__('general.invert').')';
                        }

                        return [$indicator];
                    }),
                SelectFilter::make('order_request_id')
                    ->label(__('general.linked_request'))
                    ->options([
                        'with' => __('general.yes'),
                        'without' => __('general.no'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === 'with') {
                            return $query->whereNotNull('order_request_id');
                        }

                        if ($data['value'] === 'without') {
                            return $query->whereNull('order_request_id');
                        }

                        return $query;
                    }),
                SelectFilter::make('order_article_id')
                    ->label(__('general.linked_article_directory'))
                    ->options([
                        'with' => __('general.yes'),
                        'without' => __('general.no'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === 'with') {
                            return $query->whereNotNull('order_article_id');
                        }

                        if ($data['value'] === 'without') {
                            return $query->whereNull('order_article_id');
                        }

                        return $query;
                    }),
                Filter::make('marketplace')
                    ->schema([
                        Select::make('values')
                            ->label(__('general.marketplace'))
                            ->multiple()
                            ->options([
                                'frog_store' => __('general.frog_store'),
                                'metro' => __('general.metro'),
                                'amazon' => __('general.amazon'),
                                'hornbach' => __('general.hornbach'),
                                'ikea' => __('general.ikea'),
                                'bauhaus' => __('general.bauhaus'),
                            ]),
                        Toggle::make('invert')
                            ->label(__('general.invert')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        $invert = $data['invert'] ?? false;

                        return $query->where(function ($query) use ($data, $invert) {
                            foreach ($data['values'] as $value) {
                                if ($value === 'frog_store') {
                                    $invert ? $query->where('url', 'not like', '%frog_store.%') : $query->orWhere('url', 'like', '%frog_store.%');
                                }

                                if ($value === 'metro') {
                                    $invert ? $query->where('url', 'not like', '%metro.%') : $query->orWhere('url', 'like', '%metro.%');
                                }

                                if ($value === 'amazon') {
                                    if ($invert) {
                                        $query->where('url', 'not like', '%amazon.%')
                                            ->where('url', 'not like', '%amzn.%')
                                            ->where('url', 'not like', '%amzn.eu%');
                                    } else {
                                        $query->orWhere('url', 'like', '%amazon.%')
                                            ->orWhere('url', 'like', '%amzn.%')
                                            ->orWhere('url', 'like', '%amzn.eu%');
                                    }
                                }

                                if ($value === 'hornbach') {
                                    $invert ? $query->where('url', 'not like', '%hornbach.%') : $query->orWhere('url', 'like', '%hornbach.%');
                                }

                                if ($value === 'ikea') {
                                    $invert ? $query->where('url', 'not like', '%ikea.%') : $query->orWhere('url', 'like', '%ikea.%');
                                }

                                if ($value === 'bauhaus') {
                                    $invert ? $query->where('url', 'not like', '%bauhaus.%') : $query->orWhere('url', 'like', '%bauhaus.%');
                                }
                            }
                        });
                    })
                    ->indicateUsing(function (array $data): array {
                        if (empty($data['values'])) {
                            return [];
                        }

                        $options = [
                            'frog_store' => __('general.frog_store'),
                            'metro' => __('general.metro'),
                            'amazon' => __('general.amazon'),
                            'hornbach' => __('general.hornbach'),
                            'ikea' => __('general.ikea'),
                            'bauhaus' => __('general.bauhaus'),
                        ];

                        $names = collect($data['values'])->map(fn ($value) => $options[$value] ?? $value)->implode(', ');
                        $indicator = __('general.marketplace').': '.$names;

                        if ($data['invert'] ?? false) {
                            $indicator .= ' ('.__('general.invert').')';
                        }

                        return [$indicator];
                    }),
                SelectFilter::make('user_note')
                    ->label(__('general.user_note'))
                    ->options([
                        'with' => __('general.yes'),
                        'without' => __('general.no'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === 'with') {
                            return $query->whereNotNull('user_note');
                        }

                        if ($data['value'] === 'without') {
                            return $query->whereNull('user_note');
                        }

                        return $query;
                    }),
                Filter::make('added_by')
                    ->schema([
                        Select::make('values')
                            ->multiple()
                            ->label(__('general.added_by'))
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => User::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                            ->getOptionLabelsUsing(fn (array $values): array => User::whereIn('id', $values)->pluck('name', 'id')->toArray()),
                        Toggle::make('invert')
                            ->label(__('general.invert')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        if ($data['invert'] ?? false) {
                            return $query->whereNotIn('added_by', $data['values']);
                        }

                        return $query->whereIn('added_by', $data['values']);
                    })
                    ->indicateUsing(function (array $data): array {
                        if (empty($data['values'])) {
                            return [];
                        }
                        $indicator = __('general.added_by').': '.count($data['values']);
                        if ($data['invert'] ?? false) {
                            $indicator .= ' ('.__('general.invert').')';
                        }

                        return [$indicator];
                    }),
                Filter::make('url')
                    ->schema([
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
            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(3)
            ->recordActions([
                Action::make('approve')
                    ->label(__('general.approve'))
                    ->action(function (Model $record): void {
                        if ($record->approve() == true) {
                            Notification::make()
                                ->body(__('general.approved'))
                                ->success()
                                ->icon('heroicon-o-check')
                                ->iconColor('success')
                                ->send();
                        }
                    })
                    ->icon('heroicon-o-check')
                    ->size(Size::ExtraLarge)
                    ->requiresConfirmation()
                    ->color(Color::Green)
                    ->modalHeading(__('general.approve_order'))
                    ->modalIcon('heroicon-o-check')
                    ->modalDescription(__('general.approve_order_description'))
                    ->visible(fn (Model $record): bool => $record->canBeApproved()),
                Action::make('decline')
                    ->label('')
                    ->action(function (Model $record): void {
                        if ($record->decline() == true) {
                            Notification::make()
                                ->title(__('general.declined'))
                                ->body(__('general.moved_to_deleted_elements'))
                                ->success()
                                ->icon('heroicon-o-check')
                                ->iconColor('success')
                                ->duration(20000)
                                ->send();
                        }
                    })
                    ->icon('heroicon-o-x-mark')
                    ->size(Size::ExtraLarge)
                    ->color(Color::Red)
                    ->requiresConfirmation()
                    ->modalHeading(__('general.decline_order'))
                    ->modalIcon('heroicon-o-exclamation-triangle')
                    ->visible(fn (Model $record): bool => $record->canBeDeclined()),
                ActionGroup::make([
                    ActionGroup::make([
                        EditAction::make()
                            ->visible(function (Model $record): bool {
                                if (! empty($record->deleted_at)) {
                                    return false;
                                }

                                return true;
                            }),
                        DeleteAction::make()
                            ->modalHeading(function ($record): string {
                                return __('general.delete').': '.$record->name;
                            }),
                        RestoreAction::make()
                            ->visible(function (Model $record) {
                                if ($record->status == 'locked') {
                                    return false;
                                }

                                return true;
                            }),
                        ForceDeleteAction::make(),
                        ViewAction::make()
                            ->visible(function (Model $record): bool {
                                if (! empty($record->deleted_at)) {
                                    return false;
                                }

                                return true;
                            }),
                    ])->dropdown(false),
                    ActionGroup::make([
                        Action::make('set_status')
                            ->label(__('general.set_status'))
                            ->action(function (Model $record, array $data): void {
                                $record->update(['status' => $data['status']]);
                            })
                            ->icon('heroicon-o-ellipsis-horizontal-circle')
                            ->schema([
                                Select::make('status')
                                    ->label(__('general.status'))
                                    ->options([
                                        'on_hold' => __('general.on_hold'),
                                        'checking' => __('general.checking'),
                                        'processing' => __('general.processing'),
                                        'open' => __('general.open'),
                                        'ordered' => __('general.ordered'),
                                        'delivered' => __('general.delivered'),
                                        'partially_received' => __('general.partially_received'),
                                        'received' => __('general.received'),
                                        'rejected' => __('general.rejected'),
                                        'locked' => __('general.locked'),
                                        'refunded' => __('general.refunded'),
                                        'awaiting_approval' => __('general.awaiting_approval'),
                                        'ready_for_pickup' => __('general.ready_for_pickup'),
                                    ])
                                    ->prefixIcon('heroicon-o-ellipsis-horizontal-circle')
                                    ->required(),
                            ])
                            ->visible(function (Model $record): bool {
                                return Auth::user()->can('can-change-order-status') || Auth::user()->hasDepartmentRoleWithPermissionTo('can-change-order-status', $record->department->id);
                            }),
                    ])->dropdown(false),
                    ActionGroup::make([
                        Action::make('user_note')
                            ->label(__('general.user_note'))
                            ->action(function (Model $record, array $data): void {
                                $record->update(['user_note' => $data['note']]);
                            })
                            ->icon('heroicon-o-pencil')
                            ->schema([
                                Textarea::make('note')
                                    ->label(fn (Model $record) => __('general.user_note').' - '.$record->name)
                                    ->default(fn (Model $record) => $record->user_note)
                                    ->autosize(),
                            ])
                            ->visible(fn (Model $record) => Gate::allows('view', $record)),
                    ])->dropdown(false),
                    ActionGroup::make([
                        Action::make('article_directory_link')
                            ->url(function (Model $record) {
                                return route('filament.app.resources.order-articles.view', $record->order_article_id);
                            }, true)
                            ->visible(fn (Model $record) => (! empty($record->order_article_id) && Gate::allows('view-OrderArticle', $record->order_article_id) && $record->directoryArticle !== null))
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->label(__('general.article_directory')),
                        Action::make('order_request_link')
                            ->url(function (Model $record) {
                                return route('filament.app.resources.order-requests.view', $record->order_request_id);
                            }, true)
                            ->visible(fn (Model $record) => (! empty($record->order_request_id) && Gate::allows('view-OrderRequest', $record->order_request_id) && $record->orderRequest !== null))
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->label(__('general.order_request')),
                    ])->dropdown(false),
                ]),
            ])
            ->toolbarActions([
                BulkAction::make('export_selected')
                    ->label(__('general.export'))
                    ->icon('heroicon-o-printer')
                    ->steps([
                        Step::make(__('general.select_type'))
                            ->schema([
                                Section::make([
                                    Radio::make('export_type')
                                        ->options($export_type_options)
                                        ->descriptions([
                                            'standard' => __('general.export_filetype_standard_description'),
                                            'metro_list' => __('general.metro_list_description'),
                                        ])
                                        ->required()
                                        ->label(''),
                                ])
                                    ->description(__('general.type')),
                            ])
                            ->icon('heroicon-o-document'),
                        Step::make('select_columns')
                            ->label(__('general.select_columns'))
                            ->description(__('general.select_columns_description'))
                            ->icon('heroicon-o-list-bullet')
                            ->schema([
                                Checkbox::make('select_all')
                                    ->label(__('general.select_all'))
                                    ->reactive() // Enables live updating
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if ($state) {
                                            // If "Select All" is ticked, set all options
                                            $set('columns', array_keys(static::$export_column_options));
                                        } else {
                                            // If "Select All" is ticked off, set empty list
                                            $set('columns', ['id', 'name']);
                                        }
                                    }),
                                Section::make([
                                    CheckboxList::make('columns')
                                        ->label('')
                                        ->options(static::$export_column_options)
                                        ->columns(3)
                                        ->required()
                                        ->default(['id', 'name'])
                                        ->rules([
                                            function () {
                                                return function (string $attribute, $value, \Closure $fail) {
                                                    $required = ['id', 'name'];
                                                    $missing = array_diff($required, (array) $value);

                                                    $translatedMissing = array_map(
                                                        fn ($key) => static::$export_column_options[$key] ?? $key,
                                                        $missing
                                                    );

                                                    if (count($missing) === 1) {
                                                        $fail(__('middleware.export_field_required', [
                                                            'fields' => implode('', $translatedMissing),
                                                        ]));
                                                    } elseif (count($missing) > 1) {
                                                        $fail(__('middleware.export_fields_required', [
                                                            'fields' => implode(', ', $translatedMissing),
                                                        ]));
                                                    }
                                                };
                                            },
                                        ]),
                                ])
                                    ->visible(function (Get $get) {
                                        return $get('export_type') == 'standard';
                                    })
                                    ->description(__('general.select_columns')),
                                Section::make([
                                    TextEntry::make(__('general.no_options_available')),
                                ])
                                    ->visible(function (Get $get) {
                                        return $get('export_type') != 'standard';
                                    }),
                            ]),
                        Step::make(__('general.options'))
                            ->schema([
                                // Options for standard export
                                Section::make([
                                    Checkbox::make('calculate_total_net')
                                        ->inline()
                                        ->label(__('general.calculate_total_net')),
                                    Checkbox::make('calculate_total_gross')
                                        ->inline()
                                        ->label(__('general.calculate_total_gross')),
                                    Checkbox::make('calculate_total_returning_deposit')
                                        ->inline()
                                        ->label(__('general.calculate_total_returning_deposit')),
                                    Checkbox::make('show_who_added_order')
                                        ->inline()
                                        ->label(__('general.show_who_added_order')),
                                    Checkbox::make('show_who_approved_order')
                                        ->inline()
                                        ->label(__('general.show_who_approved_order')),
                                ])
                                    ->description(__('general.special_fields').' - ('.__('general.per_row').')')
                                    ->visible(function (Get $get) {
                                        return $get('export_type') == 'standard';
                                    }),

                                // Option for standard export
                                Section::make([
                                    Radio::make('orientation')
                                        ->label('')
                                        ->inline()
                                        ->options([
                                            'portrait' => __('general.portrait'),
                                            'landscape' => __('general.landscape'),
                                        ])
                                        ->default('landscape')
                                        ->required(),
                                ])
                                    ->description(__('general.orientation'))
                                    ->visible(function (Get $get) {
                                        return $get('export_type') == 'standard';
                                    }),

                                // When no option is available
                                Section::make([
                                    TextEntry::make(__('general.no_options_available')),
                                ])
                                    ->visible(function (Get $get) {
                                        return $get('export_type') == 'metro_list';
                                    }),
                            ])
                            ->icon('heroicon-o-puzzle-piece'),
                        Step::make(__('general.file_type'))
                            ->schema([
                                Section::make([
                                    Radio::make('file_type')
                                        ->options([
                                            'xlsx' => '.xlsx',
                                            'pdf' => '.pdf',
                                        ])
                                        ->descriptions([
                                            'xlsx' => __('general.excel_table'),
                                            'pdf' => __('general.pdf_file'),
                                        ])
                                        ->required()
                                        ->label(''),
                                ])->description(__('general.file_type')),
                            ])
                            ->icon('heroicon-o-cog-6-tooth'),
                    ])
                    ->action(function (Collection $records, array $data, $table) {
                        try {
                            $data['image'] = $data['image'] ?? null;

                            if (! empty($data['image'])) {
                                try {
                                    $data['image'] = Storage::disk('s3')->temporaryUrl($data['image'], now()->addMinutes(30));
                                } catch (\Throwable $e) {
                                    $data['image'] = Storage::disk(config('filesystems.default'))->path($data['image']);
                                }
                            }

                            $data['records'] = $records->filter(fn ($record) => $record->status !== 'locked');

                            if ($data['records']->count() < 1) {
                                Notification::make()
                                    ->body(__('general.no_entries'))
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $timestamp = Carbon::now('Europe/Berlin')->format('Y_m_d_H_i_s');
                            $exportType = $data['export_type'] ?? 'standard';
                            $fileType = $data['file_type'] ?? 'xlsx';

                            $exportConfig = [
                                'metro_list' => [
                                    'class' => MetroExport::class,
                                    'filename' => __('general.metro_list').' - '.__('general.orders'),
                                    'params' => [$data['records']],
                                ],
                                'standard' => [
                                    'class' => OrderStandardExport::class,
                                    'filename' => __('general.standard').' - '.__('general.orders'),
                                    'params' => [$data, 92, 92, ['dangerous_good', 'big_size', 'needs_truck', 'booked_to_inventory', 'instant_delivery']],
                                ],
                            ];

                            if (! isset($exportConfig[$exportType])) {
                                return response()->json(['error' => 'Invalid export type'], 400);
                            }

                            $config = $exportConfig[$exportType];
                            $filename = "{$config['filename']} - {$timestamp}.{$fileType}";
                            $exportClass = $config['class'];
                            $exportFormat = $fileType === 'pdf' ? \Maatwebsite\Excel\Excel::MPDF : \Maatwebsite\Excel\Excel::XLSX;

                            return Excel::download(new $exportClass(...$config['params']), $filename, $exportFormat);
                        } catch (Exception $e) {
                            Notification::make()
                                ->body($e->getMessage().' - '.__('general.reload_required'))
                                ->title(__('general.error'))
                                ->danger()
                                ->persistent()
                                ->send();
                            Log::error('Error: '.$e->getMessage().' - Code: '.$e->getCode().' - File: '.$e->getFile().' - Line: '.$e->getLine());
                        }
                    }),
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => Gate::allows('bulkDelete', [Auth::user(), Order::class])),
                    RestoreBulkAction::make()
                        ->visible(fn (): bool => Gate::allows('bulkRestore', [Auth::user(), Order::class])),
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
                                    'on_hold' => __('general.on_hold'),
                                    'checking' => __('general.checking'),
                                    'processing' => __('general.processing'),
                                    'open' => __('general.open'),
                                    'ordered' => __('general.ordered'),
                                    'delivered' => __('general.delivered'),
                                    'partially_received' => __('general.partially_received'),
                                    'received' => __('general.received'),
                                    'rejected' => __('general.rejected'),
                                    'locked' => __('general.locked'),
                                    'refunded' => __('general.refunded'),
                                    'awaiting_approval' => __('general.awaiting_approval'),
                                    'ready_for_pickup' => __('general.ready_for_pickup'),
                                ])
                                ->prefixIcon('heroicon-o-ellipsis-horizontal-circle')
                                ->required(),
                        ])
                        ->visible(Auth::user()->can('can-change-order-status')),
                    BulkAction::make('set_delivery_address')
                        ->label(__('general.set_delivery_address'))
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->update(['delivery_destination' => $data['delivery_destination']]);
                            }
                            Notification::make()
                                ->body(__('general.saved'))
                                ->success()
                                ->send();
                        })
                        ->icon('heroicon-o-home')
                        ->schema([
                            Textarea::make('delivery_destination')
                                ->label(__('general.delivery_destination'))
                                ->rows(7),
                        ])
                        ->visible(Auth::user()->can('update-Order')),
                    BulkAction::make('set_order_number')
                        ->label(__('general.order_number'))
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->update(['order_number' => $data['order_number']]);
                            }
                            Notification::make()
                                ->body(__('general.saved'))
                                ->success()
                                ->send();
                        })
                        ->icon('heroicon-o-hashtag')
                        ->schema([
                            TextInput::make('order_number')
                                ->label(__('general.order_number'))
                                ->required(),
                        ])
                        ->visible(Auth::user()->can('update-Order')),
                    BulkAction::make('article_number_sync')
                        ->label(__('general.article_number_sync'))
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            foreach ($records as $order) {
                                $orderArticle = $order->directoryArticle;
                                if ($orderArticle) {
                                    $order->article_number = $orderArticle->article_number;
                                    $order->save();
                                }
                            }

                            Notification::make()
                                ->body(__('general.successfully_synchronized'))
                                ->success()
                                ->icon('heroicon-o-check')
                                ->iconColor('success')
                                ->send();
                        })
                        ->visible(Auth::user()->can('can-use-article-directory-special-functions')),
                    BulkAction::make('update_price')
                        ->label(__('general.price'))
                        ->icon('heroicon-o-currency-dollar')
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $update = [];
                                if (isset($data['price_net'])) {
                                    $update['price_net'] = $data['price_net'];
                                }
                                if (isset($data['price_gross'])) {
                                    $update['price_gross'] = $data['price_gross'];
                                }
                                if (isset($data['tax_rate'])) {
                                    $update['tax_rate'] = $data['tax_rate'];
                                }
                                $record->update($update);
                            }
                            Notification::make()
                                ->body(__('general.saved'))
                                ->success()
                                ->send();
                        })
                        ->schema([
                            Checkbox::make('auto_calculate')
                                ->label(__('general.auto_calculate'))
                                ->default(true)
                                ->formatStateUsing(fn ($state) => $state === null || ! $state ? true : $state)
                                ->live(),
                            TextInput::make('tax_rate')
                                ->label(__('general.tax_rate'))
                                ->numeric()
                                ->default(19)
                                ->visible(fn (Get $get) => $get('auto_calculate'))
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    if (! $get('auto_calculate')) {
                                        return;
                                    }
                                    $taxRate = (float) ($get('tax_rate') ?? 0);
                                    $net = (float) ($get('price_net') ?? 0);
                                    $set('price_gross', round($net * (1 + $taxRate / 100), 2));
                                }),
                            TextInput::make('price_net')
                                ->label(__('general.price_net'))
                                ->numeric()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                    if (! $get('auto_calculate')) {
                                        return;
                                    }
                                    $taxRate = (float) ($get('tax_rate') ?? 0);
                                    if ($state !== null) {
                                        $set('price_gross', round((float) $state * (1 + $taxRate / 100), 2));
                                    }
                                }),
                            TextInput::make('price_gross')
                                ->label(__('general.price_gross'))
                                ->numeric()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                    if (! $get('auto_calculate')) {
                                        return;
                                    }
                                    $taxRate = (float) ($get('tax_rate') ?? 0);
                                    if ($state !== null && $taxRate > 0) {
                                        $set('price_net', round((float) $state / (1 + $taxRate / 100), 2));
                                    } elseif ($state !== null) {
                                        $set('price_net', (float) $state);
                                    }
                                }),
                        ])
                        ->visible(Auth::user()->can('bulk-update-order-price')),
                    BulkAction::make('returning_deposit_sync')
                        ->label(__('general.returning_deposit_sync'))
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            foreach ($records as $order) {
                                $orderArticle = $order->directoryArticle;
                                if ($orderArticle) {
                                    $order->returning_deposit = $orderArticle->returning_deposit;
                                    $order->save();
                                }
                            }

                            Notification::make()
                                ->body(__('general.successfully_synchronized'))
                                ->success()
                                ->icon('heroicon-o-check')
                                ->iconColor('success')
                                ->send();
                        })
                        ->visible(Auth::user()->can('can-use-article-directory-special-functions')),
                    BulkActionGroup::make([
                        BulkAction::make('approve_order')
                            ->label(__('general.approve'))
                            ->icon('heroicon-o-check')
                            ->color(Color::Green)
                            ->requiresConfirmation()
                            ->modalHeading(__('general.approve_order'))
                            ->modalIcon('heroicon-o-check')
                            ->modalDescription(__('general.approve_order_description'))
                            ->action(function (Collection $records) {
                                $approved_elements_counter = 0;

                                foreach ($records as $order) {
                                    if ($order->approve()) {
                                        $approved_elements_counter++;
                                    }
                                }

                                if ($approved_elements_counter > 0) {
                                    Notification::make()
                                        ->body($approved_elements_counter.' '.__('general.approved'))
                                        ->success()
                                        ->icon('heroicon-o-check')
                                        ->iconColor('success')
                                        ->send();

                                    return;
                                }

                                Notification::make()
                                    ->body(__('general.nothing_to_approve'))
                                    ->warning()
                                    ->icon('heroicon-o-exclamation-triangle')
                                    ->iconColor('warning')
                                    ->send();
                            })
                            ->visible(function (): bool {
                                return Auth::user()->hasAnyDepartmentRoleWithPermissionTo('can-approve-orders') || Auth::user()->can('can-approve-orders-for-other-departments');
                            }),
                        BulkAction::make('decline_order')
                            ->label(__('general.decline'))
                            ->icon('heroicon-o-x-mark')
                            ->Color(Color::Red)
                            ->requiresConfirmation()
                            ->modalHeading(__('general.decline_order'))
                            ->modalIcon('heroicon-o-exclamation-triangle')
                            ->action(function (Collection $records) {
                                $declined_elements_counter = 0;

                                foreach ($records as $order) {
                                    if ($order->decline()) {
                                        $declined_elements_counter++;
                                    }
                                }

                                if ($declined_elements_counter > 0) {
                                    Notification::make()
                                        ->title($declined_elements_counter.' '.__('general.declined'))
                                        ->body(__('general.moved_to_deleted_elements'))
                                        ->success()
                                        ->icon('heroicon-o-check')
                                        ->iconColor('success')
                                        ->duration(20000)
                                        ->send();

                                    return;
                                }

                                Notification::make()
                                    ->body(__('general.nothing_to_decline'))
                                    ->warning()
                                    ->icon('heroicon-o-exclamation-triangle')
                                    ->iconColor('warning')
                                    ->send();
                            })
                            ->visible(function (): bool {
                                return Auth::user()->hasAnyDepartmentRoleWithPermissionTo('can-decline-orders') || Auth::user()->can('can-decline-orders-for-other-departments');
                            }),
                    ])
                        ->dropdown(false),
                ]),
            ])
            ->headerActions([
                Action::make('statusDescriptions')
                    ->label(__('general.status_descriptions_title'))
                    ->action(function () {
                        // This action opens up the modal
                    })
                    ->modalContent(function () {
                        return view('components.order_status_description');
                    })
                    ->modalHeading(__('general.status_descriptions_title'))
                    ->icon('heroicon-o-question-mark-circle')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(fn ($action) => $action->label(__('general.close'))),
            ])
            ->checkIfRecordIsSelectableUsing(
                fn (Model $record): bool => $record->status != 'locked',
            )
            ->groups([
                Group::make('name')
                    ->label(__('general.name'))
                    ->collapsible(),
                Group::make('created_at')
                    ->label(__('general.order_date'))
                    ->date()
                    ->collapsible(),
                Group::make('status')
                    ->label(__('general.status'))
                    ->collapsible(),
                Group::make('department.name')
                    ->label(__('general.department'))
                    ->collapsible(),
            ])
            ->defaultGroup('department.name')
            ->deferLoading()
            ->searchDebounce('750ms')
            ->persistSortInSession();
    }
}

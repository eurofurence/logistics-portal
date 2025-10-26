<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\Department;
use App\Models\OrderEvent;
use Illuminate\Support\Str;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class HeaderOrderAction
{
    public static function make(): Action
    {
        return Action::make('Order')
            ->label(function (Model $record): string {
                return __('general.make_order') . ': ' . Str::limit($record->name, 25, '...');
            })
            ->icon('heroicon-o-shopping-bag')
            ->schema([
                TextInput::make('quantity')
                    ->label(__('general.quantity'))
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->maxValue(1000)
                    ->required(),
                Select::make('order_event')
                    ->label(__('general.order_event'))
                    ->required()
                    ->exists('order_events', 'id')
                    ->options(function (): array {
                        $options = Auth::user()->can('can-always-order')
                            ? OrderEvent::withoutTrashed()->pluck('name', 'id')->toArray()
                            : OrderEvent::where('locked', false)
                            ->where(function ($query) {
                                $query->whereNull('order_deadline')
                                    ->orWhere('order_deadline', '>', now());
                            })
                            ->withoutTrashed()
                            ->pluck('name', 'id')
                            ->toArray();

                        return $options;
                    })
                    ->default(function () {
                        $options = Auth::user()->can('can-always-order')
                            ? OrderEvent::withoutTrashed()->pluck('id')->toArray()
                            : OrderEvent::where('locked', false)
                            ->where(function ($query) {
                                $query->whereNull('order_deadline')
                                    ->orWhere('order_deadline', '>', now());
                            })
                            ->withoutTrashed()
                            ->pluck('id')->toArray();

                        return count($options) === 1 ? $options[0] : null;
                    }),
                Select::make('department')
                    ->label(__('general.department'))
                    ->required()
                    ->exists('departments', 'id')
                    ->options(function (): array {
                        $options = Auth::user()->can('can-create-orders-for-other-departments')
                            ? Department::withoutTrashed()->pluck('name', 'id')->toArray()
                            : Auth::user()->getDepartmentsWithPermission('can-place-order')->pluck('name', 'id')->toArray();

                        return $options;
                    }),
                Textarea::make('comment')
                    ->label(__('general.comment'))
                    ->maxLength(1000)
                    ->nullable()
                    ->rows(5)
            ])
            ->action(function (Model $record, array $data): void {
                if (Gate::allows('order', $record)) {
                    $order = new Order();

                    $order->name = $record->name;
                    $order->order_event_id = $data['order_event'];
                    $order->amount = $data['quantity'];
                    $order->department_id = $data['department'];
                    $order->description = $record->description;
                    $order->comment = $data['comment'];
                    $order->url = $record->url;
                    $order->currency = $record->currency;
                    $order->picture = $record->picture;
                    $order->tax_rate = $record->tax_rate;
                    $order->price_gross = $record->price_gross;
                    $order->price_net = $record->price_net;
                    $order->order_article_id = $record->id;
                    $order->article_number = $record->article_number;
                    $order->returning_deposit = $record->returning_deposit;

                    if (Auth::user()->hasDepartmentRoleWithPermissionTo('order-needs-approval', $order->department_id)) {
                        $order->status = 'awaiting_approval';
                    }

                    $save_result = $order->save();

                    if ($save_result) {
                        Notification::make()
                            ->title(__('general.order_added'))
                            ->icon('heroicon-o-check')
                            ->iconColor('success')
                            ->body(__('general.go_to_orders_for_overview'))
                            ->success()
                            ->actions([
                                Action::make('view')
                                    ->button()
                                    ->label(__('general.overview'))
                                    ->url(route('filament.app.resources.orders.index'), true)
                                    ->visible(fn(Order $record): bool => Gate::allows('viewAny', $record))
                            ])
                            ->send();
                    } else {
                        if ($order->added_to_existing) {
                            Notification::make()
                                ->title(__('general.order_added'))
                                ->icon('heroicon-o-check')
                                ->iconColor('success')
                                ->body(__('general.added_to_existing_order'))
                                ->success()
                                ->actions([
                                    Action::make('view')
                                        ->button()
                                        ->label(__('general.overview'))
                                        ->url(route('filament.app.resources.orders.index'), true)
                                        ->visible(fn(Order $record): bool => Gate::allows('viewAny', $record))
                                ])
                                ->send();
                        } else {
                            Notification::make()
                                ->title(__('general.error'))
                                ->icon('heroicon-o-face-frown')
                                ->iconColor('danger')
                                ->body(__('general.warning_entry_not_added'))
                                ->danger()
                                ->send();
                        }
                    }
                } else {
                    Notification::make()
                        ->title(__('general.error'))
                        ->icon('heroicon-o-exclamation-circle')
                        ->iconColor('danger')
                        ->body(__('general.unauthorized_department_access'))
                        ->danger()
                        ->send();
                };
            })
            ->visible(function (Model $record) {
                return Gate::allows('order', $record);
            });
    }
}

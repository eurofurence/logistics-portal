<?php

namespace App\Filament\App\Resources\OrderRequestResource\Pages;

use App\Models\Order;
use Filament\Actions;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\App\Resources\OrderRequestResource;
use Filament\Notifications\Actions\Action as NotificationAction;

class ViewOrderRequest extends ViewRecord
{
    protected static string $resource = OrderRequestResource::class;

    private $existing_order;

    protected function getHeaderActions(): array
    {
        #TODO: Add the option to select/link more then just one order per request
        $this->existing_order = Order::where('order_request_id', $this->record->id)->withoutTrashed()->first();

        return [
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil'),
            Action::make('openLink')
                ->label(__('general.open_linked_order'))
                ->url(function (): string {
                    if ($this->existing_order) {
                        #TODO: Add the option to select/link more then just one order per request
                        return route('filament.app.resources.orders.view', $this->existing_order->id);
                    }

                    return '';
                })
                ->openUrlInNewTab()
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->outlined()
                ->visible(fn() => $this->existing_order && Auth::user()->can('view-Order')),
            Action::make('create_order_from_request')
                ->label(__('general.create_order'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->visible(fn() => Gate::allows('create', Order::class) && !$this->hasLinkedOrder())
                ->action(function (Model $record) {
                    if (Order::where('order_request_id', $record->id)->exists()) {
                        Notification::make()
                            ->title(__('general.order_cannot_be_created'))
                            ->icon('heroicon-o-face-frown')
                            ->iconColor('warning')
                            ->body(__('general.order_with_this_request_id_exists'))
                            ->warning()
                            ->color('warning')
                            ->persistent()
                            ->send();
                        return false;
                    } else {
                        $order = new Order();

                        $order->name = $record->title;
                        $order->order_event_id = $record->order_event_id;
                        $order->department_id = $record->department_id;
                        $order->url = $record->url;
                        $order->order_request_id = $record->id;
                        $order->amount = $record->quantity;

                        if ($record->addedBy->hasDepartmentRoleWithPermissionTo('order-needs-approval', $record->department_id)) {
                            $order->status = 'awaiting_approval';
                        }

                        $save_result = $order->save();

                        if ($save_result) {
                            $new_order_id = Order::where('order_request_id', $record->id)->where('added_by', Auth::user()->id)->orderBy('created_at', 'desc')->first()->id;

                            Notification::make()
                                ->title(__('general.order_created'))
                                ->icon('heroicon-o-face-smile')
                                ->iconColor('success')
                                ->success()
                                ->color('success')
                                ->persistent()
                                ->actions([
                                    NotificationAction::make('redirect_button')
                                        ->label(__('general.open_this_order'))
                                        ->button()
                                        ->icon('heroicon-o-arrow-top-right-on-square')
                                        ->url(route('filament.app.resources.orders.edit', $new_order_id), true)
                                        ->visible(Gate::allows('update', Order::class))
                                ])
                                ->send();
                        } else {
                            Notification::make()
                                ->title(__('general.order_cannot_be_created'))
                                ->icon('heroicon-o-face-frown')
                                ->iconColor('warning')
                                ->warning()
                                ->color('warning')
                                ->persistent()
                                ->send();
                        }
                    }
                })
                ->outlined()
                ->requiresConfirmation()
        ];
    }

    private function hasLinkedOrder(): bool
    {
        return Order::where('order_request_id', $this->record->id)->exists();
    }
}

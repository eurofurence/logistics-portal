<?php

namespace App\Filament\App\Resources\OrderRequests\Pages;

use App\Filament\App\Resources\OrderRequests\OrderRequestResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ViewOrderRequest extends ViewRecord
{
    protected static string $resource = OrderRequestResource::class;

    private $existing_order;

    protected function getHeaderActions(): array
    {
        // TODO: Add the option to select/link more then just one order per request
        $this->existing_order = Order::where('order_request_id', $this->record->id)->withoutTrashed()->first();

        return [
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->modalHeading(function ($record): string {
                    return __('general.delete').': '.$record->title;
                }),
            EditAction::make()
                ->icon('heroicon-o-pencil'),

            Action::make('create_order_from_request')
                ->label(__('general.create_order'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->visible(fn () => Gate::allows('create', Order::class) && ! $this->hasLinkedOrder())
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
                        $order = new Order;

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
                                    Action::make('redirect_button')
                                        ->label(__('general.open_this_order'))
                                        ->button()
                                        ->icon('heroicon-o-arrow-top-right-on-square')
                                        ->url(route('filament.app.resources.orders.edit', $new_order_id), true)
                                        ->visible(Gate::allows('update', Order::class)),
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
                ->requiresConfirmation(),
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
        ];
    }

    private function hasLinkedOrder(): bool
    {
        return Order::where('order_request_id', $this->record->id)->exists();
    }
}

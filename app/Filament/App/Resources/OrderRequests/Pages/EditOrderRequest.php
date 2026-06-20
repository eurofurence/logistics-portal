<?php

namespace App\Filament\App\Resources\OrderRequests\Pages;

use App\Filament\App\Resources\OrderRequests\OrderRequestResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditOrderRequest extends EditRecord
{
    protected static string $resource = OrderRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->modalHeading(function ($record): string {
                    return __('general.delete').': '.$record->name;
                }),
            ViewAction::make()
                ->icon('heroicon-o-eye'),

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
}

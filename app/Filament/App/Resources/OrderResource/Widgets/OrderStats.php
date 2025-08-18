<?php

namespace App\Filament\App\Resources\OrderResource\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use App\Filament\App\Resources\OrderResource\Pages\ListOrders;

class OrderStats extends BaseWidget
{
    use InteractsWithPageTable;

    protected static ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return ListOrders::class;
    }

    protected function getStats(): array
    {
        $current_entries = $this->getPageTableQuery()->get(['price_net', 'amount', 'status', 'delivery_costs', 'returning_deposit', 'discount_net']);
        $totalAmount = 0;
        $totalAmountReturnDeposit = 0;
        $totalAmountShippingCosts = 0;

        foreach ($current_entries as $entry) {
            if ($entry->status != 'rejected') {
                if ($entry->price_net) {
                    $totalAmount += ($entry->amount * $entry->price_net) - $entry->discount_net;
                }

                if ($entry->delivery_costs) {
                    $totalAmountShippingCosts += $entry->delivery_costs;
                }

                if ($entry->returning_deposit) {
                    $totalAmountReturnDeposit += $entry->amount * $entry->returning_deposit;
                }
            }
        }

        return [
            Stat::make(__('general.orders'), $this->getPageTableQuery()->count())
                ->icon('heroicon-o-shopping-cart'),
            Stat::make(__('general.open_orders'), $this->getPageTableQuery()->whereIn('status', ['open', 'processing'])->count())
                ->icon('heroicon-o-arrow-path'),
            Stat::make(__('general.total_amount') . ' (' . __('general.net') . ')', number_format($totalAmount, 2) . '€')
                ->description(__('general.widget_total_amount_decription_orders'))
                ->icon('heroicon-o-currency-euro'),
            Stat::make(__('general.delivery_costs') . ' ' . __('general.and') . ' ' . __('general.returning_deposit'), number_format($totalAmountReturnDeposit + $totalAmountShippingCosts, 2) . '€')
                ->description(__('general.widget_total_amount_decription_orders') . ', ' . __('general.delivery_costs') . ': ' . number_format($totalAmountShippingCosts, 2) . '€, ' . __('general.returning_deposit') . ': ' . number_format($totalAmountReturnDeposit, 2) . '€, ' . __('general.gross'))
                ->icon('heroicon-o-currency-euro'),
        ];
    }
}

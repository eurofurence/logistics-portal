<?php

namespace App\Filament\App\Resources\Bills\Widgets;

use Filament\Widgets\Widget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use App\Filament\App\Resources\Bills\Pages\ListBills;

class BillStats extends BaseWidget
{
    use InteractsWithPageTable;

    protected ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return ListBills::class;
    }

    protected function getStats(): array
    {
        $current_entries = $this->getPageTableQuery()->get(['value', 'status', 'exchange_rate']);

        $totalAmount = 0;
        $totalAmountOpenBills = 0;

        foreach ($current_entries as $entry) {
            if ($entry->status != 'rejected') {
                $roundedEntryAmount = round(bcmul($entry->value, $entry->exchange_rate, 5), 2);
                $totalAmount += $roundedEntryAmount;

                if ($entry->status != 'done') {
                    $roundedEntryAmount = round(bcmul($entry->value, $entry->exchange_rate, 5), 2);
                    $totalAmountOpenBills += $roundedEntryAmount;
                }
            }
        }


        return [
            Stat::make(__('general.billing_plural'), $this->getPageTableQuery()->count())
                ->icon('heroicon-o-document-currency-euro'),
            Stat::make(__('general.open_invoices'), $this->getPageTableQuery()->whereIn('status', ['open', 'processing', 'on_hold', 'checking'])->count())
                ->icon('heroicon-o-arrow-path'),
            Stat::make(__('general.total_amount'), number_format($totalAmount, 2) . '€')
                ->description(__('general.thereof') . ' ' . number_format($totalAmountOpenBills, 2) . '€ ' . lcfirst(__('general.open')) . '. ' . __('general.widget_total_amount_decription_bills') . '.')
        ];
    }
}

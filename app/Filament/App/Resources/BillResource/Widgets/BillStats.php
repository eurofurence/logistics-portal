<?php

namespace App\Filament\App\Resources\BillResource\Widgets;

use Filament\Widgets\Widget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use App\Filament\App\Resources\BillResource\Pages\ListBills;

class BillStats extends BaseWidget
{
    use InteractsWithPageTable;

    protected static ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return ListBills::class;
    }

    protected function getStats(): array
    {
        $current_entries = $this->getPageTableQuery()->get(['value', 'status']);

        return [
            Stat::make(__('general.billing_plural'), $this->getPageTableQuery()->count())
                ->icon('heroicon-o-document-currency-euro'),
            Stat::make(__('general.open_invoices'), $this->getPageTableQuery()->whereIn('status', ['open', 'processing', 'on_hold', 'checking'])->count())
                ->icon('heroicon-o-arrow-path'),
        ];
    }
}

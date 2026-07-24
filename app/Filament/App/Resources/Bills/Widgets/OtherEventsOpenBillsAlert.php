<?php

namespace App\Filament\App\Resources\Bills\Widgets;

use App\Filament\App\Resources\Bills\Pages\ListBills;
use App\Models\Bill;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class OtherEventsOpenBillsAlert extends Widget
{
    use InteractsWithPageTable;

    protected string $view = 'filament.app.resources.bills.widgets.other-events-open-bills-alert';

    public int|string|array $columnSpan = 'full';

    protected function getTablePage(): string
    {
        return ListBills::class;
    }

    public function getOpenBillsCount(): int
    {
        $selectedEventIds = array_filter(collect($this->tableFilters['order_event_id'] ?? [])->flatten()->toArray());

        if (empty($selectedEventIds)) {
            return 0;
        }

        $user = Auth::user();

        return Bill::whereNotIn('order_event_id', $selectedEventIds)
            ->whereIn('status', ['open', 'processing', 'on_hold', 'checking'])
            ->when(! $user->can('can-see-all-bills'), function ($query) use ($user) {
                return $query->whereIn('department_id', $user->getDepartmentsWithPermission('view-Bill')->pluck('id'));
            })
            ->count();
    }
}

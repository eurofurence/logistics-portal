<?php

namespace App\Filament\App\Resources\Bills\Widgets;

use App\Filament\App\Resources\Bills\Pages\ListBills;
use App\Models\Bill;
use App\Models\OrderEvent;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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
        $selectedEventIds = $this->getSelectedEventIds();

        if (empty($selectedEventIds)) {
            return 0;
        }

        return $this->getOpenBillsQuery($selectedEventIds)->count();
    }

    public function getEventsWithOpenBills(): Collection
    {
        $selectedEventIds = $this->getSelectedEventIds();

        if (empty($selectedEventIds)) {
            return collect();
        }

        return $this->getOpenBillsQuery($selectedEventIds)
            ->with('connected_event')
            ->get()
            ->pluck('connected_event.name')
            ->unique()
            ->sort();
    }

    protected function getSelectedEventIds(): array
    {
        $selectedEventIds = array_filter(collect($this->tableFilters['order_event_id'] ?? [])->flatten()->toArray());

        if (empty($selectedEventIds)) {
            return [];
        }

        return $selectedEventIds;
    }

    protected function getOpenBillsQuery(array $selectedEventIds): Builder
    {
        $user = Auth::user();

        return Bill::whereNotIn('order_event_id', $selectedEventIds)
            ->whereIn('status', ['open', 'processing', 'on_hold', 'checking'])
            ->when(! $user->can('can-see-all-bills'), function ($query) use ($user) {
                return $query->whereIn('department_id', $user->getDepartmentsWithPermission('view-Bill')->pluck('id'));
            });
    }
}

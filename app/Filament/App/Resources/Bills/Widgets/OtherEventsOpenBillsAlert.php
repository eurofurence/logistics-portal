<?php

namespace App\Filament\App\Resources\Bills\Widgets;

use App\Models\Bill;
use App\Models\OrderEvent;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class OtherEventsOpenBillsAlert extends Widget
{
    protected string $view = 'filament.app.resources.bills.widgets.other-events-open-bills-alert';

    public int|string|array $columnSpan = 'full';

    public function getOpenBillsCount(): int
    {
        // Wir ignorieren explizit jegliche Tabellen-Query-Modifikationen,
        // da wir direkt auf das Bill Model zugreifen und nicht InteractsWithPageTable nutzen.
        $activeEvent = OrderEvent::where('is_active', true)->first();

        if (! $activeEvent) {
            return 0;
        }

        $user = Auth::user();

        return Bill::where('order_event_id', '!=', $activeEvent->id)
            ->whereIn('status', ['open', 'processing', 'on_hold', 'checking'])
            ->when(! $user->can('can-see-all-bills'), function ($query) use ($user) {
                return $query->whereIn('department_id', $user->getDepartmentsWithPermission('view-Bill')->pluck('id'));
            })
            ->count();
    }
}

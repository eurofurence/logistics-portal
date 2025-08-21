<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\StatusHistory;
use Illuminate\Support\Facades\Auth;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        if ($order->wasChanged('status')) {
            StatusHistory::create([
                'model_type' => Order::class,
                'model_id' => $order->id,
                'icon' => 'heroicon-o-arrow-path',
                'title' => 'timeline.status_changed',
                'description' => [
                    'key' => 'timeline.from_to',
                    'params' => [
                        'old' => $order->getOriginal('status'),
                        'new' => $order->status,
                    ],
                ],
                'user_id' => Auth::id(),
            ]);
        }
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}

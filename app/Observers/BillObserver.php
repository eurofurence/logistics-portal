<?php

namespace App\Observers;

use App\Models\Bill;
use App\Models\StatusHistory;
use Illuminate\Support\Facades\Auth;

class BillObserver
{
    /**
     * Handle the Bill "created" event.
     */
    public function created(Bill $bill): void
    {
        StatusHistory::create([
                'model_type' => Bill::class,
                'model_id' => $bill->id,
                'icon' => 'heroicon-o-plus',
                'title' => 'timeline.created', // Key instead of text
                'description' => [
                    'key' => 'timeline.bill_was_created',
                ],
                'user_id' => Auth::id(),
            ]);
    }

    /**
     * Handle the Bill "updated" event.
     */
    public function updated(Bill $bill): void
    {
        if ($bill->wasChanged('status')) {
            StatusHistory::create([
                'model_type' => Bill::class,
                'model_id' => $bill->id,
                'icon' => 'heroicon-o-arrow-path',
                'title' => 'timeline.status_changed',
                'description' => [
                    'key' => 'timeline.from_to',
                    'params' => [
                        'old' => $bill->getOriginal('status'),
                        'new' => $bill->status,
                    ],
                ],
                'user_id' => Auth::id(),
            ]);
        }
    }

    /**
     * Handle the Bill "deleted" event.
     */
    public function deleted(Bill $bill): void
    {
        //
    }

    /**
     * Handle the Bill "restored" event.
     */
    public function restored(Bill $bill): void
    {
        //
    }

    /**
     * Handle the Bill "force deleted" event.
     */
    public function forceDeleted(Bill $bill): void
    {
        //
    }
}

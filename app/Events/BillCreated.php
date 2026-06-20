<?php

namespace App\Events;

use App\Models\Bill;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BillCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Bill $bill;

    /**
     * Create a new event instance.
     */
    public function __construct(Bill $model)
    {
        $this->bill = $model;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('bills'),
        ];
    }
}

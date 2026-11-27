<?php

namespace App\Models;

use Database\Factories\OrderEventNotificationDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderEventNotificationDelivery extends Model
{
    /** @use HasFactory<OrderEventNotificationDeliveryFactory> */
    use HasFactory;

    protected $fillable = [
        'order_event_id', 'user_id', 'type', 'cycle_key', 'batch_key', 'channel',
        'automatic', 'deadline_at', 'status', 'attempts', 'sent_at', 'last_error',
    ];

    protected function casts(): array
    {
        return ['automatic' => 'boolean', 'deadline_at' => 'datetime', 'sent_at' => 'datetime'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(OrderEvent::class, 'order_event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

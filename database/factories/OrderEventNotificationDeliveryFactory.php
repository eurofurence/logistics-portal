<?php

namespace Database\Factories;

use App\Models\OrderEvent;
use App\Models\OrderEventNotificationDelivery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderEventNotificationDelivery>
 */
class OrderEventNotificationDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_event_id' => OrderEvent::factory(),
            'user_id' => User::factory(),
            'type' => 'deadline',
            'cycle_key' => fake()->sha256(),
            'batch_key' => fake()->uuid(),
            'channel' => 'mail',
            'automatic' => true,
            'status' => 'pending',
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'department_id' => Department::factory(),
            'order_event_id' => OrderEvent::factory(),
            'added_by' => User::factory(),
            'edited_by' => User::factory(),
            'status' => 'open',
        ];
    }
}

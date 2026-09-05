<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\Department;
use App\Models\OrderEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bill>
 */
class BillFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'value' => fake()->randomFloat(2, 1, 1000),
            'currency' => 'EUR',
            'status' => 'open',
            'department_id' => Department::factory(),
            'order_event_id' => OrderEvent::factory(),
            'added_by' => User::factory(),
            'edited_by' => User::factory(),
            'payment_deadline' => null,
        ];
    }
}

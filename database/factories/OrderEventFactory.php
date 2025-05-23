<?php

namespace Database\Factories;

use App\Models\OrderEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderEvent>
 */
class OrderEventFactory extends Factory
{
    protected $model = OrderEvent::class;

    public function definition()
    {
        return [
            'locked' => false,
            'is_active' => true,
            'order_deadline' => now()->addDay(),
            'name' => $this->faker->word
        ];
    }
}

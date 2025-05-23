<?php

namespace Database\Factories;

use App\Models\OrderRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderRequest>
 */
class OrderRequestFactory extends Factory
{
    protected $model = OrderRequest::class;

    public function definition()
    {
        return [
            'department_id' => 1,
            'status' => 0,
            'title' => $this->faker->word,
            'order_event_id' => 1,
        ];
    }
}

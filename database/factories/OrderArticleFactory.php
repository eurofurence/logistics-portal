<?php

namespace Database\Factories;

use App\Models\OrderArticle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderArticle>
 */
class OrderArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->sentence(3),
            'price_net' => 10,
            'price_gross' => 11.90,
            'tax_rate' => 19,
            'returning_deposit' => 0,
            'currency' => 'EUR',
            'auto_calculate' => true,
        ];
    }
}

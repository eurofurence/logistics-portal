<?php

namespace Database\Factories;

use App\Models\ClubMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ClubMember> */
class ClubMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sona_name' => fake()->userName(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'street_address' => fake()->streetAddress(),
            'postal_code' => fake()->postcode(),
            'city' => fake()->city(),
            'country' => 'DE',
            'email' => fake()->safeEmail(),
            'birth_date' => '1990-01-01',
            'joined_at' => '2020-01-01',
            'left_at' => null,
            'phone' => null,
            'comment' => null,
        ];
    }
}

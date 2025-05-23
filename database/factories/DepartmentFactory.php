<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    /**
     * @var [type]
     */
    protected $model = Department::class;

    public function definition()
    {
        // Create or get existing users for added_by and edited_by
        $addedByUser = User::factory()->create();
        $editedByUser = User::factory()->create();

        return [
            'name' => $this->faker->name(),
            'added_by' => $addedByUser->id,
            'edited_by' => $editedByUser->id,
        ];
    }
}

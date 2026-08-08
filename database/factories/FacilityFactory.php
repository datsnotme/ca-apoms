<?php

namespace Database\Factories;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Facility>
 */
class FacilityFactory extends Factory
{
    protected $model = Facility::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->streetName().' '.fake()->buildingNumber(),
            'type' => fake()->randomElement(['Classroom', 'Laboratory', 'Farm', 'Greenhouse', 'Field Location']),
            'department_id' => null,
            'location' => fake()->city(),
            'capacity' => fake()->numberBetween(10, 100),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\FacultyTraining;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FacultyTraining>
 */
class FacultyTrainingFactory extends Factory
{
    protected $model = FacultyTraining::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'provider' => fake()->company(),
            'training_type' => 'seminar',
            'start_date' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'end_date' => null,
            'hours' => fake()->numberBetween(4, 40),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\FacultyAward;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FacultyAward>
 */
class FacultyAwardFactory extends Factory
{
    protected $model = FacultyAward::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => 'Outstanding Faculty Award',
            'awarding_body' => fake()->company(),
            'date_awarded' => fake()->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
            'description' => null,
        ];
    }
}

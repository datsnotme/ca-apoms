<?php

namespace Database\Factories;

use App\Models\FacultyEducation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FacultyEducation>
 */
class FacultyEducationFactory extends Factory
{
    protected $model = FacultyEducation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'level' => 'masters',
            'degree' => 'Master of Science in Agriculture',
            'field_of_study' => fake()->words(2, true),
            'institution' => fake()->company().' University',
            'year_completed' => fake()->numberBetween(2000, 2024),
        ];
    }
}

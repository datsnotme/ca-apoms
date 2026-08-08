<?php

namespace Database\Factories;

use App\Models\FacultyProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FacultyProfile>
 */
class FacultyProfileFactory extends Factory
{
    protected $model = FacultyProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'academic_rank' => fake()->randomElement(['Instructor I', 'Assistant Professor', 'Associate Professor']),
            'employment_status' => 'full_time',
            'specialization' => fake()->words(2, true),
            'office_location' => null,
            'date_hired' => null,
            'bio' => null,
        ];
    }
}

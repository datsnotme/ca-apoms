<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\StudentInterventionFollowup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentInterventionFollowup>
 */
class StudentInterventionFollowupFactory extends Factory
{
    protected $model = StudentInterventionFollowup::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'description' => fake()->sentence(),
            'status' => 'pending',
            'created_by' => User::factory(),
        ];
    }
}

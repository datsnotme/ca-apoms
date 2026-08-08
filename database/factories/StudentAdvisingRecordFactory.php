<?php

namespace Database\Factories;

use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentAdvisingRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentAdvisingRecord>
 */
class StudentAdvisingRecordFactory extends Factory
{
    protected $model = StudentAdvisingRecord::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'adviser_id' => User::factory(),
            'semester_id' => Semester::factory(),
            'session_date' => now()->toDateString(),
            'summary' => fake()->sentence(),
            'recommendations' => null,
            'follow_up_required' => false,
        ];
    }
}

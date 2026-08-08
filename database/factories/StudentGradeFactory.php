<?php

namespace Database\Factories;

use App\Models\EnrollmentCourse;
use App\Models\StudentGrade;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentGrade>
 */
class StudentGradeFactory extends Factory
{
    protected $model = StudentGrade::class;

    public function definition(): array
    {
        return [
            'enrollment_course_id' => EnrollmentCourse::factory(),
            'grade' => null,
            'status' => 'draft',
            // grade_change_logs.changed_by is NOT NULL — the model's saved()
            // hook writes a log row on every grade value, so an encoder is
            // required even when a test creates a StudentGrade directly
            // instead of going through GradeService::encode().
            'encoded_by' => User::factory(),
        ];
    }
}

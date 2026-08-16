<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\StudentHistoricalGrade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentHistoricalGrade>
 */
class StudentHistoricalGradeFactory extends Factory
{
    protected $model = StudentHistoricalGrade::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'academic_year_label' => '2022-2023',
            'semester_label' => 'First Semester',
            'program_label' => 'BSBIO',
            'course_code' => strtoupper(fake()->unique()->lexify('GEC???')),
            'course_title' => fake()->words(3, true),
            'lecture_hours' => 3,
            'laboratory_hours' => 0,
            'units' => 3,
            'grade' => '2.50',
        ];
    }
}

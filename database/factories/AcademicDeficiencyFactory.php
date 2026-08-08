<?php

namespace Database\Factories;

use App\Models\AcademicDeficiency;
use App\Models\CurriculumCourse;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicDeficiency>
 */
class AcademicDeficiencyFactory extends Factory
{
    protected $model = AcademicDeficiency::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'curriculum_course_id' => CurriculumCourse::factory(),
            'deficiency_type' => 'failed',
            'detected_at' => now(),
        ];
    }
}

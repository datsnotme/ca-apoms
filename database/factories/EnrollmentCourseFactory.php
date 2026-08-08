<?php

namespace Database\Factories;

use App\Models\ClassSection;
use App\Models\EnrollmentCourse;
use App\Models\StudentEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnrollmentCourse>
 */
class EnrollmentCourseFactory extends Factory
{
    protected $model = EnrollmentCourse::class;

    public function definition(): array
    {
        return [
            'student_enrollment_id' => StudentEnrollment::factory(),
            'class_section_id' => ClassSection::factory(),
            'status' => 'Enrolled',
        ];
    }
}

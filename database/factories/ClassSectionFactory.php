<?php

namespace Database\Factories;

use App\Models\ClassSection;
use App\Models\Course;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSection>
 */
class ClassSectionFactory extends Factory
{
    protected $model = ClassSection::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'semester_id' => Semester::factory(),
            'section_label' => strtoupper(fake()->unique()->lexify('?')),
            'max_students' => 40,
            'status' => 'open',
        ];
    }
}

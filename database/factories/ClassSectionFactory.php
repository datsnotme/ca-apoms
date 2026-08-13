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
            // Three characters (17,576 possibilities), not one (26) — a
            // single-letter pool collided under this suite's growing
            // volume of ClassSection::factory() calls across unrelated
            // test files, since Faker's unique() tracks uniqueness
            // globally per test process, not scoped to course+semester
            // the way the DB constraint actually is.
            'section_label' => strtoupper(fake()->unique()->lexify('???')),
            'max_students' => 40,
            'status' => 'open',
        ];
    }
}

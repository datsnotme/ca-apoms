<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurriculumCourse>
 */
class CurriculumCourseFactory extends Factory
{
    protected $model = CurriculumCourse::class;

    public function definition(): array
    {
        return [
            'curriculum_id' => Curriculum::factory(),
            'course_id' => Course::factory(),
            'year_level' => 1,
            'semester' => 'FIRST',
            'is_required' => true,
            'units' => 3,
            'sequence_order' => 1,
        ];
    }
}

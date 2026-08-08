<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'code' => strtoupper(fake()->unique()->lexify('CRS???')),
            'title' => fake()->words(3, true),
            'units' => 3,
            'lecture_hours' => 3,
            'laboratory_hours' => 0,
            'category' => 'crop_science',
            'is_active' => true,
        ];
    }
}

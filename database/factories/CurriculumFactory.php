<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Curriculum>
 */
class CurriculumFactory extends Factory
{
    protected $model = Curriculum::class;

    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'effective_academic_year_id' => AcademicYear::factory(),
            'code' => strtoupper(fake()->unique()->lexify('CURR???')),
            'name' => fake()->words(3, true),
            'status' => 'active',
        ];
    }
}

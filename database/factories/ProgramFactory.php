<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'code' => strtoupper(fake()->unique()->lexify('PROG???')),
            'name' => 'BS '.fake()->words(2, true),
            'degree_type' => 'Bachelor',
            'required_total_units' => fake()->numberBetween(150, 190),
            'duration_years' => 4,
            'status' => 'active',
        ];
    }
}

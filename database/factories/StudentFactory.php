<?php

namespace Database\Factories;

use App\Models\Curriculum;
use App\Models\Department;
use App\Models\Program;
use App\Models\Student;
use App\Models\YearLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'student_number' => fake()->unique()->numerify('####-#####'),
            'surname' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->lastName(),
            'sex' => fake()->randomElement(['Male', 'Female']),
            'birth_date' => fake()->dateTimeBetween('-25 years', '-17 years')->format('Y-m-d'),
            'department_id' => Department::factory(),
            'program_id' => Program::factory(),
            'curriculum_id' => Curriculum::factory(),
            // Reuse an existing year level if the test already seeded one
            // (level is unique) rather than always minting a new random
            // one, which risked colliding with a level a test had already
            // explicitly created.
            'year_level_id' => YearLevel::query()->inRandomOrder()->value('id') ?? YearLevel::factory(),
            'admission_type' => 'Freshman',
            'date_admitted' => fake()->dateTimeBetween('-4 years', 'now')->format('Y-m-d'),
            'classification' => 'regular',
            'status' => 'active',
        ];
    }
}

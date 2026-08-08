<?php

namespace Database\Factories;

use App\Enums\ResearchProjectStatus;
use App\Models\Department;
use App\Models\ResearchProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResearchProject>
 */
class ResearchProjectFactory extends Factory
{
    protected $model = ResearchProject::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(ResearchProjectStatus::cases())->value,
            'start_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'end_date' => null,
            'funding_source' => fake()->randomElement(['Internal', 'DOST-PCAARRD', 'CHED', null]),
            'created_by' => User::factory(),
        ];
    }
}

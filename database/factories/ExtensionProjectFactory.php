<?php

namespace Database\Factories;

use App\Enums\ExtensionProjectStatus;
use App\Models\Department;
use App\Models\ExtensionProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExtensionProject>
 */
class ExtensionProjectFactory extends Factory
{
    protected $model = ExtensionProject::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(ExtensionProjectStatus::cases())->value,
            'start_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'end_date' => null,
            'funding_source' => fake()->randomElement(['Internal', 'DA-ATI', 'LGU Partnership', null]),
            'created_by' => User::factory(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'assigned_to' => null,
            'due_date' => fake()->dateTimeBetween('now', '+1 month'),
            'status' => 'pending',
            'created_by' => User::factory(),
        ];
    }
}

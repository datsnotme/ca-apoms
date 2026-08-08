<?php

namespace Database\Factories;

use App\Models\ExtensionActivity;
use App\Models\ExtensionProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExtensionActivity>
 */
class ExtensionActivityFactory extends Factory
{
    protected $model = ExtensionActivity::class;

    public function definition(): array
    {
        return [
            'extension_project_id' => ExtensionProject::factory(),
            'title' => fake()->sentence(6),
            'activity_type' => fake()->randomElement(['Training Seminar', 'Outreach Event', 'Field Demonstration', 'Medical Mission']),
            'description' => fake()->paragraph(),
            'activity_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'location' => fake()->city(),
            'created_by' => User::factory(),
        ];
    }
}

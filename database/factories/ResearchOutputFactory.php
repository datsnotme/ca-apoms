<?php

namespace Database\Factories;

use App\Models\ResearchOutput;
use App\Models\ResearchProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResearchOutput>
 */
class ResearchOutputFactory extends Factory
{
    protected $model = ResearchOutput::class;

    public function definition(): array
    {
        return [
            'research_project_id' => ResearchProject::factory(),
            'title' => fake()->sentence(6),
            'type' => fake()->randomElement(['Journal Article', 'Conference Paper', 'Patent', 'Technical Report']),
            'description' => fake()->paragraph(),
            'output_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'reference_url' => fake()->optional()->url(),
            'created_by' => User::factory(),
        ];
    }
}

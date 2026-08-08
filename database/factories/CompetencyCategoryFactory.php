<?php

namespace Database\Factories;

use App\Models\CompetencyCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompetencyCategory>
 */
class CompetencyCategoryFactory extends Factory
{
    protected $model = CompetencyCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'description' => null,
            'sort_order' => 0,
        ];
    }
}

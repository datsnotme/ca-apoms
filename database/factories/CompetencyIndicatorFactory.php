<?php

namespace Database\Factories;

use App\Models\CompetencyCategory;
use App\Models\CompetencyIndicator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompetencyIndicator>
 */
class CompetencyIndicatorFactory extends Factory
{
    protected $model = CompetencyIndicator::class;

    public function definition(): array
    {
        return [
            'competency_category_id' => CompetencyCategory::factory(),
            'title' => fake()->sentence(4),
            'description' => null,
            'sort_order' => 0,
        ];
    }
}

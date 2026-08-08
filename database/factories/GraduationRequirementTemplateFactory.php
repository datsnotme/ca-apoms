<?php

namespace Database\Factories;

use App\Models\GraduationRequirementTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GraduationRequirementTemplate>
 */
class GraduationRequirementTemplateFactory extends Factory
{
    protected $model = GraduationRequirementTemplate::class;

    public function definition(): array
    {
        return [
            'program_id' => null,
            'title' => fake()->sentence(3),
            'is_required' => true,
            'sort_order' => 0,
        ];
    }
}

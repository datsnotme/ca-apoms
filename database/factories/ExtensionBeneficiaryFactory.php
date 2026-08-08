<?php

namespace Database\Factories;

use App\Models\ExtensionBeneficiary;
use App\Models\ExtensionProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExtensionBeneficiary>
 */
class ExtensionBeneficiaryFactory extends Factory
{
    protected $model = ExtensionBeneficiary::class;

    public function definition(): array
    {
        return [
            'extension_project_id' => ExtensionProject::factory(),
            'beneficiary_name' => fake()->company(),
            'beneficiary_type' => fake()->randomElement(['Farmer Cooperative', 'Individual Farmer', 'Community', 'LGU']),
            'count' => fake()->numberBetween(1, 200),
            'location' => fake()->city(),
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}

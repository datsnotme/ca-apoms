<?php

namespace Database\Factories;

use App\Models\College;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<College>
 */
class CollegeFactory extends Factory
{
    protected $model = College::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'name' => 'College of '.fake()->word(),
            'address' => fake()->address(),
            'contact_email' => fake()->unique()->companyEmail(),
            'contact_phone' => fake()->phoneNumber(),
        ];
    }
}

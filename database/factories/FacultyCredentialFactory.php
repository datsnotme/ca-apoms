<?php

namespace Database\Factories;

use App\Models\FacultyCredential;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FacultyCredential>
 */
class FacultyCredentialFactory extends Factory
{
    protected $model = FacultyCredential::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'Licensed Agriculturist',
            'issuing_body' => 'Professional Regulation Commission',
            'license_number' => fake()->numerify('#######'),
            'issued_date' => fake()->dateTimeBetween('-5 years', '-1 year')->format('Y-m-d'),
            'expiry_date' => null,
        ];
    }
}

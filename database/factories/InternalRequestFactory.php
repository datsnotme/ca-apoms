<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\InternalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InternalRequest>
 */
class InternalRequestFactory extends Factory
{
    protected $model = InternalRequest::class;

    public function definition(): array
    {
        return [
            'requester_id' => User::factory(),
            'department_id' => Department::factory(),
            'type' => fake()->randomElement(['Leave', 'Resource', 'Equipment']),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => 'pending',
        ];
    }
}

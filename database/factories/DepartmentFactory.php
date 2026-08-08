<?php

namespace Database\Factories;

use App\Models\College;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'college_id' => College::factory(),
            'code' => strtoupper(fake()->unique()->lexify('DEPT???')),
            'name' => 'Department of '.fake()->words(2, true),
            'description' => fake()->sentence(),
            'office_location' => fake()->buildingNumber().' Building',
            'status' => 'active',
        ];
    }
}

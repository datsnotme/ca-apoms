<?php

namespace Database\Factories;

use App\Enums\EquipmentStatus;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipment>
 */
class EquipmentFactory extends Factory
{
    protected $model = Equipment::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'type' => fake()->randomElement(['Microscope', 'Laptop', 'Tractor', 'Measuring Tool', 'Projector']),
            'department_id' => null,
            'facility_id' => null,
            'serial_number' => fake()->unique()->bothify('SN-####-????'),
            'status' => EquipmentStatus::Available->value,
            'description' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}

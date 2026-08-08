<?php

namespace Database\Factories;

use App\Models\EquipmentBorrowing;
use App\Models\EquipmentReturn;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentReturn>
 */
class EquipmentReturnFactory extends Factory
{
    protected $model = EquipmentReturn::class;

    public function definition(): array
    {
        return [
            'equipment_borrowing_id' => EquipmentBorrowing::factory(),
            'returned_at' => now(),
            'condition_on_return' => fake()->randomElement(['Good', 'Minor wear', 'Needs cleaning']),
            'notes' => fake()->optional()->sentence(),
            'recorded_by' => User::factory(),
        ];
    }
}

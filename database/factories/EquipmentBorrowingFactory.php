<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\EquipmentBorrowing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentBorrowing>
 */
class EquipmentBorrowingFactory extends Factory
{
    protected $model = EquipmentBorrowing::class;

    public function definition(): array
    {
        return [
            'equipment_id' => Equipment::factory(),
            'borrowed_by' => User::factory(),
            'borrowed_at' => now(),
            'expected_return_at' => now()->addWeek(),
            'purpose' => fake()->sentence(),
            'recorded_by' => User::factory(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\EquipmentMaintenance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentMaintenance>
 */
class EquipmentMaintenanceFactory extends Factory
{
    protected $model = EquipmentMaintenance::class;

    public function definition(): array
    {
        return [
            'equipment_id' => Equipment::factory(),
            'description' => fake()->sentence(),
            'started_at' => now()->toDateString(),
            'completed_at' => null,
            'performed_by' => fake()->optional()->name(),
            'notes' => fake()->optional()->sentence(),
            'recorded_by' => User::factory(),
        ];
    }
}

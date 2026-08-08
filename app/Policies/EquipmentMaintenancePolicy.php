<?php

namespace App\Policies;

use App\Models\Equipment;
use App\Models\EquipmentMaintenance;
use App\Models\User;

class EquipmentMaintenancePolicy
{
    public function create(User $user, Equipment $equipment): bool
    {
        return app(EquipmentPolicy::class)->update($user, $equipment);
    }

    public function update(User $user, EquipmentMaintenance $maintenance): bool
    {
        return app(EquipmentPolicy::class)->update($user, $maintenance->equipment);
    }
}

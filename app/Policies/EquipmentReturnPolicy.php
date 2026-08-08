<?php

namespace App\Policies;

use App\Models\Equipment;
use App\Models\User;

class EquipmentReturnPolicy
{
    public function create(User $user, Equipment $equipment): bool
    {
        return app(EquipmentPolicy::class)->update($user, $equipment);
    }
}

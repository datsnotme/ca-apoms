<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Equipment;
use App\Models\User;

/**
 * Reuses operations.manage/operations.view — same reasoning as
 * FacilityPolicy (Phase 7C): the spec has no equipment-specific permission
 * row, so this follows the same free-design-judgment precedent Phase 6
 * established rather than a literal spec requirement.
 */
class EquipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('operations.view');
    }

    public function view(User $user, Equipment $equipment): bool
    {
        if (! $user->can('operations.view')) {
            return false;
        }

        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value]) || $equipment->department_id === null) {
            return true;
        }

        return $equipment->department_id === $user->department_id;
    }

    public function create(User $user): bool
    {
        return $user->can('operations.manage');
    }

    public function update(User $user, Equipment $equipment): bool
    {
        if (! $user->can('operations.manage')) {
            return false;
        }

        return $user->hasRole(RoleName::Administrator->value)
            || $equipment->department_id === $user->department_id;
    }

    public function delete(User $user, Equipment $equipment): bool
    {
        return $this->update($user, $equipment);
    }
}

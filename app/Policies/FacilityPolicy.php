<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Facility;
use App\Models\User;

/**
 * Reuses operations.manage/operations.view — the spec has no facilities-
 * specific permission row (unlike Research/Extension's explicit 🅿7 row), so
 * this follows the same free-design-judgment precedent as Phase 6: a
 * facility is an administrative/operational asset, not a personal artifact
 * owned by an individual creator, so it fits the existing operations.manage
 * scope (Admin any department, Department Head own department only) rather
 * than the ownership-based model used for Research/Extension. See
 * ROLE_PERMISSIONS.md and ASSUMPTIONS.md.
 */
class FacilityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('operations.view');
    }

    public function view(User $user, Facility $facility): bool
    {
        if (! $user->can('operations.view')) {
            return false;
        }

        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value]) || $facility->department_id === null) {
            return true;
        }

        return $facility->department_id === $user->department_id;
    }

    public function create(User $user): bool
    {
        return $user->can('operations.manage');
    }

    public function update(User $user, Facility $facility): bool
    {
        if (! $user->can('operations.manage')) {
            return false;
        }

        return $user->hasRole(RoleName::Administrator->value)
            || $facility->department_id === $user->department_id;
    }

    public function delete(User $user, Facility $facility): bool
    {
        return $this->update($user, $facility);
    }
}

<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\FacultyProfile;
use App\Models\User;

class FacultyProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('faculty-profiles.view');
    }

    public function view(User $user, FacultyProfile $profile): bool
    {
        if (! $user->can('faculty-profiles.view')) {
            return false;
        }

        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])) {
            return true;
        }

        if ($user->hasRole(RoleName::DepartmentHead->value)) {
            return $profile->user->department_id === $user->department_id;
        }

        return $profile->user_id === $user->id;
    }

    /**
     * Admin can edit any field on any profile; a faculty member can edit a
     * limited set of fields on their own profile — see FacultyProfileRequest
     * for which fields differ by actor.
     */
    public function update(User $user, FacultyProfile $profile): bool
    {
        return $user->can('faculty-profiles.manage') || $profile->user_id === $user->id;
    }
}

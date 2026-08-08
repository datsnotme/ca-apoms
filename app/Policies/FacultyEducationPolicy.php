<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\FacultyEducation;
use App\Models\User;

class FacultyEducationPolicy
{
    public function create(User $user): bool
    {
        return $user->can('faculty-profiles.manage') && $user->hasRole(RoleName::Administrator->value);
    }

    public function update(User $user, FacultyEducation $education): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, FacultyEducation $education): bool
    {
        return $this->create($user);
    }
}

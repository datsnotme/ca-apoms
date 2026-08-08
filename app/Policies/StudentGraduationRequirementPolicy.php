<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\StudentGraduationRequirement;
use App\Models\User;

class StudentGraduationRequirementPolicy
{
    public function update(User $user, StudentGraduationRequirement $requirement): bool
    {
        return $user->can('graduation.manage') && $user->hasRole(RoleName::Administrator->value);
    }
}

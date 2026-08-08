<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Curriculum;
use App\Models\User;

class CurriculumPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('curricula.view');
    }

    public function view(User $user, Curriculum $curriculum): bool
    {
        if (! $user->can('curricula.view')) {
            return false;
        }

        return $user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])
            || $curriculum->program->department_id === $user->department_id;
    }

    public function create(User $user): bool
    {
        return $user->can('curricula.manage');
    }

    public function update(User $user, Curriculum $curriculum): bool
    {
        if (! $user->can('curricula.manage')) {
            return false;
        }

        return $user->hasRole(RoleName::Administrator->value)
            || $curriculum->program->department_id === $user->department_id;
    }

    public function delete(User $user, Curriculum $curriculum): bool
    {
        if (! $user->can('curricula.manage')) {
            return false;
        }

        return $user->hasRole(RoleName::Administrator->value)
            || $curriculum->program->department_id === $user->department_id;
    }
}

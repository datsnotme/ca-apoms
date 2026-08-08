<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Program;
use App\Models\User;

class ProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('programs.view');
    }

    public function view(User $user, Program $program): bool
    {
        if (! $user->can('programs.view')) {
            return false;
        }

        return $user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])
            || $program->department_id === $user->department_id;
    }

    public function create(User $user): bool
    {
        return $user->can('programs.manage');
    }

    public function update(User $user, Program $program): bool
    {
        return $user->can('programs.manage');
    }

    public function delete(User $user, Program $program): bool
    {
        return $user->can('programs.manage');
    }

    public function restore(User $user, Program $program): bool
    {
        return $user->can('programs.manage');
    }
}

<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('departments.view');
    }

    public function view(User $user, Department $department): bool
    {
        if (! $user->can('departments.view')) {
            return false;
        }

        return $user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])
            || $department->id === $user->department_id;
    }

    public function create(User $user): bool
    {
        return $user->can('departments.manage');
    }

    public function update(User $user, Department $department): bool
    {
        return $user->can('departments.manage');
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->can('departments.manage');
    }

    public function restore(User $user, Department $department): bool
    {
        return $user->can('departments.manage');
    }
}

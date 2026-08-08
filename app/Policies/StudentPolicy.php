<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('students.view');
    }

    public function view(User $user, Student $student): bool
    {
        if (! $user->can('students.view')) {
            return false;
        }

        return $user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])
            || $student->department_id === $user->department_id;
    }

    public function create(User $user): bool
    {
        return $user->can('students.manage');
    }

    public function update(User $user, Student $student): bool
    {
        if (! $user->can('students.manage')) {
            return false;
        }

        return $user->hasRole(RoleName::Administrator->value)
            || $student->department_id === $user->department_id;
    }

    public function delete(User $user, Student $student): bool
    {
        return $this->update($user, $student);
    }

    public function import(User $user): bool
    {
        return $user->can('students.import');
    }

    /**
     * Narrower than view(): the spec scopes Faculty to their own advisees
     * only, not the whole department — see ROLE_PERMISSIONS.md.
     */
    public function viewProgress(User $user, Student $student): bool
    {
        if (! $user->can('progress.view')) {
            return false;
        }

        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])) {
            return true;
        }

        if ($user->hasRole(RoleName::DepartmentHead->value)) {
            return $student->department_id === $user->department_id;
        }

        return $student->adviser_id === $user->id;
    }
}

<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\StudentEnrollment;
use App\Models\User;

class StudentEnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('enrollment.view');
    }

    public function view(User $user, StudentEnrollment $enrollment): bool
    {
        if (! $user->can('enrollment.view')) {
            return false;
        }

        return $user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])
            || $enrollment->student->department_id === $user->department_id;
    }

    public function create(User $user): bool
    {
        return $user->can('enrollment.manage');
    }

    public function update(User $user, StudentEnrollment $enrollment): bool
    {
        if (! $user->can('enrollment.manage')) {
            return false;
        }

        return $user->hasRole(RoleName::Administrator->value)
            || $enrollment->student->department_id === $user->department_id;
    }

    public function delete(User $user, StudentEnrollment $enrollment): bool
    {
        return $this->update($user, $enrollment);
    }
}

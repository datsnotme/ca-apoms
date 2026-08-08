<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Student;
use App\Models\StudentInterventionFollowup;
use App\Models\User;

/**
 * Reuses advising.manage/advising.view — the spec groups "record
 * advising/intervention" as a single capability line, not two separate
 * permissions. See ROLE_PERMISSIONS.md.
 */
class StudentInterventionFollowupPolicy
{
    public function view(User $user, StudentInterventionFollowup $followup): bool
    {
        if (! $user->can('advising.view')) {
            return false;
        }

        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])) {
            return true;
        }

        if ($user->hasRole(RoleName::DepartmentHead->value)) {
            return $followup->student->department_id === $user->department_id;
        }

        return $followup->student->adviser_id === $user->id;
    }

    public function create(User $user, Student $student): bool
    {
        if (! $user->can('advising.manage')) {
            return false;
        }

        if ($user->hasRole(RoleName::Administrator->value)) {
            return true;
        }

        if ($user->hasRole(RoleName::DepartmentHead->value)) {
            return $student->department_id === $user->department_id;
        }

        return $student->adviser_id === $user->id;
    }

    public function update(User $user, StudentInterventionFollowup $followup): bool
    {
        if (! $user->can('advising.manage')) {
            return false;
        }

        if ($user->hasRole(RoleName::Administrator->value)) {
            return true;
        }

        if ($user->hasRole(RoleName::DepartmentHead->value)) {
            return $followup->student->department_id === $user->department_id;
        }

        return $followup->student->adviser_id === $user->id || $followup->assigned_to === $user->id;
    }

    public function delete(User $user, StudentInterventionFollowup $followup): bool
    {
        return $this->update($user, $followup);
    }
}

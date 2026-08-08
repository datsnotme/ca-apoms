<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Student;
use App\Models\StudentAdvisingRecord;
use App\Models\User;

class StudentAdvisingRecordPolicy
{
    public function view(User $user, StudentAdvisingRecord $record): bool
    {
        if (! $user->can('advising.view')) {
            return false;
        }

        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])) {
            return true;
        }

        if ($user->hasRole(RoleName::DepartmentHead->value)) {
            return $record->student->department_id === $user->department_id;
        }

        return $record->student->adviser_id === $user->id;
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

    public function update(User $user, StudentAdvisingRecord $record): bool
    {
        if (! $user->can('advising.manage')) {
            return false;
        }

        if ($user->hasRole(RoleName::Administrator->value)) {
            return true;
        }

        if ($user->hasRole(RoleName::DepartmentHead->value)) {
            return $record->student->department_id === $user->department_id;
        }

        return $record->adviser_id === $user->id;
    }

    public function delete(User $user, StudentAdvisingRecord $record): bool
    {
        return $this->update($user, $record);
    }
}

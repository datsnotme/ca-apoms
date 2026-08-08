<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\ClassSection;
use App\Models\User;

class ClassSectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('enrollment.view');
    }

    public function view(User $user, ClassSection $classSection): bool
    {
        if (! $user->can('enrollment.view')) {
            return false;
        }

        return $user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])
            || $classSection->course->department_id === $user->department_id;
    }

    public function create(User $user): bool
    {
        return $user->can('enrollment.manage');
    }

    public function update(User $user, ClassSection $classSection): bool
    {
        if (! $user->can('enrollment.manage')) {
            return false;
        }

        return $user->hasRole(RoleName::Administrator->value)
            || $classSection->course->department_id === $user->department_id;
    }

    public function delete(User $user, ClassSection $classSection): bool
    {
        return $this->update($user, $classSection);
    }

    public function viewGrades(User $user, ClassSection $classSection): bool
    {
        if (! $user->can('grades.view')) {
            return false;
        }

        return $user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])
            || $classSection->course->department_id === $user->department_id;
    }

    public function encodeGrades(User $user, ClassSection $classSection): bool
    {
        if (! $user->can('grades.encode')) {
            return false;
        }

        if ($user->hasRole(RoleName::Administrator->value)) {
            return true;
        }

        return $classSection->facultyAssignments()->where('faculty_id', $user->id)->exists();
    }

    public function reviewGrades(User $user, ClassSection $classSection): bool
    {
        if (! $user->can('grades.review')) {
            return false;
        }

        return $user->hasRole(RoleName::Administrator->value)
            || $classSection->course->department_id === $user->department_id;
    }
}

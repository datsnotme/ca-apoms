<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('courses.view');
    }

    public function view(User $user, Course $course): bool
    {
        if (! $user->can('courses.view')) {
            return false;
        }

        return $user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])
            || $course->department_id === $user->department_id;
    }

    public function create(User $user): bool
    {
        return $user->can('courses.manage');
    }

    public function update(User $user, Course $course): bool
    {
        return $user->can('courses.manage');
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->can('courses.manage');
    }
}

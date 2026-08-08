<?php

namespace App\Policies;

use App\Models\Semester;
use App\Models\User;

class SemesterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('academic-terms.view');
    }

    public function view(User $user, Semester $semester): bool
    {
        return $user->can('academic-terms.view');
    }

    public function create(User $user): bool
    {
        return $user->can('academic-terms.manage');
    }

    public function update(User $user, Semester $semester): bool
    {
        return $user->can('academic-terms.manage');
    }

    public function delete(User $user, Semester $semester): bool
    {
        return $user->can('academic-terms.manage');
    }
}

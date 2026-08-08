<?php

namespace App\Policies;

use App\Models\AcademicYear;
use App\Models\User;

class AcademicYearPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('academic-terms.view');
    }

    public function view(User $user, AcademicYear $academicYear): bool
    {
        return $user->can('academic-terms.view');
    }

    public function create(User $user): bool
    {
        return $user->can('academic-terms.manage');
    }

    public function update(User $user, AcademicYear $academicYear): bool
    {
        return $user->can('academic-terms.manage');
    }

    public function delete(User $user, AcademicYear $academicYear): bool
    {
        return $user->can('academic-terms.manage');
    }
}

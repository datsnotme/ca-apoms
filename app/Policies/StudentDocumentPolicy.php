<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;

class StudentDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('student-documents.view');
    }

    public function view(User $user, StudentDocument $document): bool
    {
        if (! $user->can('student-documents.view')) {
            return false;
        }

        return $user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])
            || $document->student->department_id === $user->department_id;
    }

    public function upload(User $user, Student $student): bool
    {
        if (! $user->can('student-documents.manage')) {
            return false;
        }

        return $user->hasRole(RoleName::Administrator->value)
            || $student->department_id === $user->department_id;
    }

    public function verify(User $user, StudentDocument $document): bool
    {
        if (! $user->can('student-documents.manage')) {
            return false;
        }

        return $user->hasRole(RoleName::Administrator->value)
            || $document->student->department_id === $user->department_id;
    }

    public function delete(User $user, StudentDocument $document): bool
    {
        return $this->verify($user, $document);
    }
}

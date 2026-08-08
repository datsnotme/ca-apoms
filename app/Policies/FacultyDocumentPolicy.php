<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\FacultyDocument;
use App\Models\User;

class FacultyDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('faculty-profiles.view');
    }

    public function view(User $user, FacultyDocument $document): bool
    {
        if (! $user->can('faculty-profiles.view')) {
            return false;
        }

        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])) {
            return true;
        }

        if ($user->hasRole(RoleName::DepartmentHead->value)) {
            return $document->user->department_id === $user->department_id;
        }

        return $document->user_id === $user->id;
    }

    /**
     * A faculty member may upload their own supporting documents — the file
     * enters as "pending" and is not treated as verified fact until an
     * Admin reviews it, unlike the un-gated Education/Credential/Training/
     * Award fields (Phase 5B), which have no such review step.
     */
    public function upload(User $user, User $targetFaculty): bool
    {
        if ($user->id === $targetFaculty->id) {
            return true;
        }

        return $user->can('faculty-profiles.manage') && $user->hasRole(RoleName::Administrator->value);
    }

    public function verify(User $user, FacultyDocument $document): bool
    {
        return $user->can('faculty-profiles.manage') && $user->hasRole(RoleName::Administrator->value);
    }

    public function delete(User $user, FacultyDocument $document): bool
    {
        return $this->verify($user, $document);
    }
}

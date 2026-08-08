<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\FacultyCredential;
use App\Models\User;

class FacultyCredentialPolicy
{
    public function create(User $user): bool
    {
        return $user->can('faculty-profiles.manage') && $user->hasRole(RoleName::Administrator->value);
    }

    public function update(User $user, FacultyCredential $credential): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, FacultyCredential $credential): bool
    {
        return $this->create($user);
    }
}

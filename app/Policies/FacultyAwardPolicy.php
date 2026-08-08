<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\FacultyAward;
use App\Models\User;

class FacultyAwardPolicy
{
    public function create(User $user): bool
    {
        return $user->can('faculty-profiles.manage') && $user->hasRole(RoleName::Administrator->value);
    }

    public function update(User $user, FacultyAward $award): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, FacultyAward $award): bool
    {
        return $this->create($user);
    }
}

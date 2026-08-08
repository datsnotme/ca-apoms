<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\FacultyTraining;
use App\Models\User;

class FacultyTrainingPolicy
{
    public function create(User $user): bool
    {
        return $user->can('faculty-profiles.manage') && $user->hasRole(RoleName::Administrator->value);
    }

    public function update(User $user, FacultyTraining $training): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, FacultyTraining $training): bool
    {
        return $this->create($user);
    }
}

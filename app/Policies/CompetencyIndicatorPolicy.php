<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\CompetencyIndicator;
use App\Models\User;

class CompetencyIndicatorPolicy
{
    public function create(User $user): bool
    {
        return $user->can('graduation.manage') && $user->hasRole(RoleName::Administrator->value);
    }

    public function update(User $user, CompetencyIndicator $indicator): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, CompetencyIndicator $indicator): bool
    {
        return $this->create($user);
    }
}

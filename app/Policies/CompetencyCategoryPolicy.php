<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\CompetencyCategory;
use App\Models\User;

class CompetencyCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('graduation.view');
    }

    public function create(User $user): bool
    {
        return $user->can('graduation.manage') && $user->hasRole(RoleName::Administrator->value);
    }

    public function update(User $user, CompetencyCategory $category): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, CompetencyCategory $category): bool
    {
        return $this->create($user);
    }
}

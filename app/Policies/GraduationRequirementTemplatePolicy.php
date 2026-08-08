<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\GraduationRequirementTemplate;
use App\Models\User;

class GraduationRequirementTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('graduation.view');
    }

    public function create(User $user): bool
    {
        return $user->can('graduation.manage') && $user->hasRole(RoleName::Administrator->value);
    }

    public function update(User $user, GraduationRequirementTemplate $template): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, GraduationRequirementTemplate $template): bool
    {
        return $this->create($user);
    }
}

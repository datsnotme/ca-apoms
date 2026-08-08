<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\ExtensionProject;
use App\Models\User;

/**
 * Reuses research-extension.manage/research-extension.view — same
 * permission pair as ResearchProjectPolicy (Phase 7A), since the spec gives
 * Research and Extension an identical permission row. See
 * ROLE_PERMISSIONS.md.
 */
class ExtensionProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('research-extension.view');
    }

    public function view(User $user, ExtensionProject $project): bool
    {
        if (! $user->can('research-extension.view')) {
            return false;
        }

        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])) {
            return true;
        }

        return $project->department_id === $user->department_id;
    }

    public function create(User $user): bool
    {
        return $user->can('research-extension.manage') || $user->hasRole(RoleName::Faculty->value);
    }

    public function update(User $user, ExtensionProject $project): bool
    {
        if ($user->can('research-extension.manage')) {
            return true;
        }

        return $project->isLedBy($user);
    }

    public function delete(User $user, ExtensionProject $project): bool
    {
        return $this->update($user, $project);
    }
}

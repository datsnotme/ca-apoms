<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\ResearchProject;
use App\Models\User;

/**
 * Reuses research-extension.manage/research-extension.view — the spec gives
 * Research and Extension (Phase 7B) an identical permission row ("Submit
 * research/extension records"), so one permission pair covers both rather
 * than minting near-duplicate pairs. See ROLE_PERMISSIONS.md.
 *
 * Unlike Phase 6's operations.manage (freely granted to Department Head),
 * this phase's spec row gives Department Head view-only (👁, own dept.) and
 * Faculty manage-own (🟡, own): research-extension.manage is Administrator-
 * only in the seeder, so project leadership (a ResearchMember row with
 * is_lead=true) is the only path to update/delete for anyone else.
 */
class ResearchProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('research-extension.view');
    }

    public function view(User $user, ResearchProject $project): bool
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

    public function update(User $user, ResearchProject $project): bool
    {
        if ($user->can('research-extension.manage')) {
            return true;
        }

        return $project->isLedBy($user);
    }

    public function delete(User $user, ResearchProject $project): bool
    {
        return $this->update($user, $project);
    }
}

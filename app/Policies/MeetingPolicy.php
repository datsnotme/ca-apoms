<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Meeting;
use App\Models\User;

/**
 * Reuses operations.manage/operations.view — same shape and same
 * permission pair as AnnouncementPolicy/EventPolicy (Phase 6A). See
 * ROLE_PERMISSIONS.md.
 */
class MeetingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('operations.view');
    }

    public function view(User $user, Meeting $meeting): bool
    {
        if (! $user->can('operations.view')) {
            return false;
        }

        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value]) || $meeting->department_id === null) {
            return true;
        }

        return $meeting->department_id === $user->department_id;
    }

    public function create(User $user): bool
    {
        return $user->can('operations.manage');
    }

    public function update(User $user, Meeting $meeting): bool
    {
        if (! $user->can('operations.manage')) {
            return false;
        }

        return $user->hasRole(RoleName::Administrator->value)
            || $meeting->department_id === $user->department_id;
    }

    public function delete(User $user, Meeting $meeting): bool
    {
        return $this->update($user, $meeting);
    }
}

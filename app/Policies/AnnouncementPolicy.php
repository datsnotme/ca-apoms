<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('operations.view');
    }

    public function view(User $user, Announcement $announcement): bool
    {
        if (! $user->can('operations.view')) {
            return false;
        }

        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value]) || $announcement->department_id === null) {
            return true;
        }

        return $announcement->department_id === $user->department_id;
    }

    public function create(User $user): bool
    {
        return $user->can('operations.manage');
    }

    public function update(User $user, Announcement $announcement): bool
    {
        if (! $user->can('operations.manage')) {
            return false;
        }

        return $user->hasRole(RoleName::Administrator->value)
            || $announcement->department_id === $user->department_id;
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $this->update($user, $announcement);
    }
}

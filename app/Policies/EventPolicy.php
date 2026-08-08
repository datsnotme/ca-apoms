<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('operations.view');
    }

    public function view(User $user, Event $event): bool
    {
        if (! $user->can('operations.view')) {
            return false;
        }

        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value]) || $event->department_id === null) {
            return true;
        }

        return $event->department_id === $user->department_id;
    }

    public function create(User $user): bool
    {
        return $user->can('operations.manage');
    }

    public function update(User $user, Event $event): bool
    {
        if (! $user->can('operations.manage')) {
            return false;
        }

        return $user->hasRole(RoleName::Administrator->value)
            || $event->department_id === $user->department_id;
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }
}

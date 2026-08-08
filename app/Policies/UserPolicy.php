<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function view(User $user, User $target): bool
    {
        return $user->can('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function update(User $user, User $target): bool
    {
        return $user->can('users.manage');
    }

    public function delete(User $user, User $target): bool
    {
        // An administrator may deactivate/archive any account except their own,
        // to avoid locking every admin out of the system by accident.
        return $user->can('users.manage') && $user->id !== $target->id;
    }
}

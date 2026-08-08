<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Task;
use App\Models\User;

/**
 * Task is a personal delegation tool, not a permission-gated "operations"
 * resource — any authenticated user may create one and see their own; no
 * `tasks.*` permission exists. See ASSUMPTIONS.md.
 */
class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return $this->isParticipant($user, $task);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Task $task): bool
    {
        return $this->isParticipant($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->hasRole(RoleName::Administrator->value) || $task->created_by === $user->id;
    }

    private function isParticipant(User $user, Task $task): bool
    {
        return $user->hasRole(RoleName::Administrator->value)
            || $task->created_by === $user->id
            || $task->assigned_to === $user->id;
    }
}

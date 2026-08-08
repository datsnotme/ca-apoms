<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('operations.view');
    }

    public function view(User $user, Document $document): bool
    {
        if (! $user->can('operations.view')) {
            return false;
        }

        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value]) || $document->department_id === null) {
            return true;
        }

        return $document->department_id === $user->department_id;
    }

    public function create(User $user): bool
    {
        return $user->can('operations.manage');
    }

    public function update(User $user, Document $document): bool
    {
        if (! $user->can('operations.manage')) {
            return false;
        }

        return $user->hasRole(RoleName::Administrator->value)
            || $document->department_id === $user->department_id;
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->update($user, $document);
    }
}

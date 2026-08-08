<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\DocumentCategory;
use App\Models\User;

/**
 * Admin-only management, same shape as GraduationRequirementTemplatePolicy
 * (Phase 4A) — a small admin-configurable lookup list, not something a
 * Department Head should be able to add to freely.
 */
class DocumentCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('operations.view');
    }

    public function create(User $user): bool
    {
        return $user->can('operations.manage') && $user->hasRole(RoleName::Administrator->value);
    }

    public function delete(User $user, DocumentCategory $category): bool
    {
        return $this->create($user);
    }
}

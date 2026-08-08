<?php

namespace App\Policies;

use App\Enums\InternalRequestStatus;
use App\Enums\RoleName;
use App\Models\InternalRequest;
use App\Models\User;

class InternalRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('operations.view');
    }

    public function view(User $user, InternalRequest $request): bool
    {
        if (! $user->can('operations.view')) {
            return false;
        }

        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])) {
            return true;
        }

        if ($user->hasRole(RoleName::DepartmentHead->value)) {
            return $request->department_id === $user->department_id;
        }

        return $request->requester_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('operations.view');
    }

    /**
     * Approve/reject. Reserved for whoever holds department-manage
     * authority (Admin college-wide, Department Head their own
     * department) — and never the request's own requester, even an Admin
     * or Department Head, to avoid self-approval.
     */
    public function review(User $user, InternalRequest $request): bool
    {
        if ($request->requester_id === $user->id || $request->status !== InternalRequestStatus::Pending) {
            return false;
        }

        if (! $user->can('operations.manage')) {
            return false;
        }

        return $user->hasRole(RoleName::Administrator->value)
            || $request->department_id === $user->department_id;
    }

    public function cancel(User $user, InternalRequest $request): bool
    {
        return $request->requester_id === $user->id && $request->status === InternalRequestStatus::Pending;
    }
}

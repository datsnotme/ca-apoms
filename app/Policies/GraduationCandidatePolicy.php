<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\GraduationCandidate;
use App\Models\User;

class GraduationCandidatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('graduation.view');
    }

    public function view(User $user, GraduationCandidate $candidate): bool
    {
        if (! $user->can('graduation.view')) {
            return false;
        }

        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])) {
            return true;
        }

        if ($user->hasRole(RoleName::DepartmentHead->value)) {
            return $candidate->student->department_id === $user->department_id;
        }

        return $candidate->competencyEvaluators()->where('evaluator_id', $user->id)->exists();
    }

    /**
     * Nominating a candidate and managing their requirement checklist is an
     * Admin-only registrar function per the spec's matrix — Department Head
     * and Dean's Phase 4 involvement is limited to recommend/approve
     * (Phase 4C), never preparation.
     */
    public function create(User $user): bool
    {
        return $user->can('graduation.manage') && $user->hasRole(RoleName::Administrator->value);
    }

    public function update(User $user, GraduationCandidate $candidate): bool
    {
        return $user->can('graduation.manage') && $user->hasRole(RoleName::Administrator->value);
    }

    public function delete(User $user, GraduationCandidate $candidate): bool
    {
        return $this->update($user, $candidate);
    }

    /**
     * Recommending a candidate is the Department Head's own judgment call on
     * their own department's candidates — never Admin, Dean, or another
     * department's head, per the spec's recommend/approve split (Phase 4C).
     */
    public function recommend(User $user, GraduationCandidate $candidate): bool
    {
        return $user->can('graduation.view')
            && $user->hasRole(RoleName::DepartmentHead->value)
            && $candidate->student->department_id === $user->department_id;
    }

    /**
     * Approving/rejecting a recommendation is the Dean's own judgment call,
     * college-wide — never Admin or the recommending Department Head.
     */
    public function decide(User $user, GraduationCandidate $candidate): bool
    {
        return $user->can('graduation.view') && $user->hasRole(RoleName::Dean->value);
    }
}

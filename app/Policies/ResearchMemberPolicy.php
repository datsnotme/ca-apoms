<?php

namespace App\Policies;

use App\Models\ResearchMember;
use App\Models\ResearchProject;
use App\Models\User;

class ResearchMemberPolicy
{
    public function create(User $user, ResearchProject $project): bool
    {
        return app(ResearchProjectPolicy::class)->update($user, $project);
    }

    public function delete(User $user, ResearchMember $member): bool
    {
        return app(ResearchProjectPolicy::class)->update($user, $member->project);
    }
}

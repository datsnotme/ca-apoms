<?php

namespace App\Policies;

use App\Models\ExtensionMember;
use App\Models\ExtensionProject;
use App\Models\User;

class ExtensionMemberPolicy
{
    public function create(User $user, ExtensionProject $project): bool
    {
        return app(ExtensionProjectPolicy::class)->update($user, $project);
    }

    public function delete(User $user, ExtensionMember $member): bool
    {
        return app(ExtensionProjectPolicy::class)->update($user, $member->project);
    }
}

<?php

namespace App\Policies;

use App\Models\ExtensionActivity;
use App\Models\ExtensionProject;
use App\Models\User;

class ExtensionActivityPolicy
{
    public function create(User $user, ExtensionProject $project): bool
    {
        return app(ExtensionProjectPolicy::class)->update($user, $project);
    }

    public function delete(User $user, ExtensionActivity $activity): bool
    {
        return app(ExtensionProjectPolicy::class)->update($user, $activity->project);
    }
}

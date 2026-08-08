<?php

namespace App\Policies;

use App\Models\ResearchOutput;
use App\Models\ResearchProject;
use App\Models\User;

class ResearchOutputPolicy
{
    public function create(User $user, ResearchProject $project): bool
    {
        return app(ResearchProjectPolicy::class)->update($user, $project);
    }

    public function delete(User $user, ResearchOutput $output): bool
    {
        return app(ResearchProjectPolicy::class)->update($user, $output->project);
    }
}

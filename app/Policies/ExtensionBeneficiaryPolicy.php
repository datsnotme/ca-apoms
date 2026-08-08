<?php

namespace App\Policies;

use App\Models\ExtensionBeneficiary;
use App\Models\ExtensionProject;
use App\Models\User;

class ExtensionBeneficiaryPolicy
{
    public function create(User $user, ExtensionProject $project): bool
    {
        return app(ExtensionProjectPolicy::class)->update($user, $project);
    }

    public function delete(User $user, ExtensionBeneficiary $beneficiary): bool
    {
        return app(ExtensionProjectPolicy::class)->update($user, $beneficiary->project);
    }
}

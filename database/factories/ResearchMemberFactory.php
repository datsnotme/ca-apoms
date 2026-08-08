<?php

namespace Database\Factories;

use App\Models\ResearchMember;
use App\Models\ResearchProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResearchMember>
 */
class ResearchMemberFactory extends Factory
{
    protected $model = ResearchMember::class;

    public function definition(): array
    {
        return [
            'research_project_id' => ResearchProject::factory(),
            'user_id' => User::factory(),
            'is_lead' => false,
            'added_by' => User::factory(),
        ];
    }
}

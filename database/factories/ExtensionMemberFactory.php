<?php

namespace Database\Factories;

use App\Models\ExtensionMember;
use App\Models\ExtensionProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExtensionMember>
 */
class ExtensionMemberFactory extends Factory
{
    protected $model = ExtensionMember::class;

    public function definition(): array
    {
        return [
            'extension_project_id' => ExtensionProject::factory(),
            'user_id' => User::factory(),
            'is_lead' => false,
            'added_by' => User::factory(),
        ];
    }
}

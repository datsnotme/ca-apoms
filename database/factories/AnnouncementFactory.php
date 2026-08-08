<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        return [
            'department_id' => null,
            'title' => fake()->sentence(6),
            'body' => fake()->paragraphs(2, true),
            'created_by' => User::factory(),
        ];
    }
}

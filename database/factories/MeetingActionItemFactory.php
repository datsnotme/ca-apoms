<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\MeetingActionItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetingActionItem>
 */
class MeetingActionItemFactory extends Factory
{
    protected $model = MeetingActionItem::class;

    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'description' => fake()->sentence(),
            'assigned_to' => null,
            'due_date' => fake()->dateTimeBetween('now', '+1 month'),
            'status' => 'pending',
            'created_by' => User::factory(),
        ];
    }
}

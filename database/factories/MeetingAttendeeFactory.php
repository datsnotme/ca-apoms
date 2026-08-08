<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\MeetingAttendee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetingAttendee>
 */
class MeetingAttendeeFactory extends Factory
{
    protected $model = MeetingAttendee::class;

    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'user_id' => User::factory(),
            'attended' => false,
            'invited_by' => User::factory(),
        ];
    }
}

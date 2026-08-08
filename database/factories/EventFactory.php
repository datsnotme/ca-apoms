<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $startAt = fake()->dateTimeBetween('now', '+2 months');

        return [
            'department_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'start_at' => $startAt,
            'end_at' => (clone $startAt)->modify('+2 hours'),
            'location' => fake()->buildingNumber().' Building',
            'created_by' => User::factory(),
        ];
    }
}

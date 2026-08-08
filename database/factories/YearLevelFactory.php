<?php

namespace Database\Factories;

use App\Models\YearLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<YearLevel>
 */
class YearLevelFactory extends Factory
{
    protected $model = YearLevel::class;

    public function definition(): array
    {
        $level = fake()->unique()->numberBetween(1, 6);

        return [
            'level' => $level,
            'label' => match ($level) {
                1 => '1st Year',
                2 => '2nd Year',
                3 => '3rd Year',
                4 => '4th Year',
                5 => '5th Year',
                default => '6th Year',
            },
        ];
    }
}

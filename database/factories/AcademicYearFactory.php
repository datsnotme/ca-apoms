<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    public function definition(): array
    {
        $start = fake()->unique()->numberBetween(2015, 2035);

        return [
            'start_year' => $start,
            'end_year' => $start + 1,
            'is_current' => false,
        ];
    }
}

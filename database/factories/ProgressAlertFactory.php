<?php

namespace Database\Factories;

use App\Models\ProgressAlert;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgressAlert>
 */
class ProgressAlertFactory extends Factory
{
    protected $model = ProgressAlert::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'alert_type' => 'low_gwa',
            'severity' => 'warning',
            'message' => 'Sample alert.',
            'triggered_at' => now(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\GraduationCandidate;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GraduationCandidate>
 */
class GraduationCandidateFactory extends Factory
{
    protected $model = GraduationCandidate::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'semester_id' => Semester::factory(),
            'status' => 'nominated',
            'gwa_snapshot' => '1.50',
            'completion_percentage_snapshot' => '100.00',
            'deficiency_count_snapshot' => 0,
            'nominated_by' => User::factory(),
            'nominated_at' => now(),
            'created_by' => User::factory(),
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\Department;
use App\Models\Program;
use App\Models\Student;
use App\Models\YearLevel;
use Illuminate\Database\Seeder;

/**
 * Sample students covering the classification/status combinations staff will
 * encounter, so the Students module has demo data without needing an Excel
 * import first. One curriculum per seeded program is created here if the
 * program has none yet, since curricula are otherwise only built via the UI.
 */
class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $academicYear = AcademicYear::where('is_current', true)->first();
        $yearLevels = YearLevel::whereIn('level', [1, 2, 3, 4])->orderBy('level')->get();

        $scenarios = [
            ['classification' => 'regular', 'status' => 'active'],
            ['classification' => 'regular', 'status' => 'active'],
            ['classification' => 'irregular', 'status' => 'active'],
            ['classification' => 'transferee', 'status' => 'active'],
            ['classification' => 'shiftee', 'status' => 'active'],
            ['classification' => 'returning', 'status' => 'on_leave'],
            ['classification' => 'regular', 'status' => 'dropped'],
            ['classification' => 'graduating', 'status' => 'active'],
        ];

        foreach (Department::with('programs')->get() as $department) {
            $program = $department->programs->first();

            if (! $program) {
                continue;
            }

            $curriculum = Curriculum::firstOrCreate(
                ['program_id' => $program->id, 'code' => "{$program->code}-2026"],
                [
                    'effective_academic_year_id' => $academicYear?->id,
                    'name' => "{$program->name} Curriculum (2026)",
                    'required_total_units' => $program->required_total_units,
                    'status' => 'active',
                ]
            );

            foreach ($scenarios as $index => $scenario) {
                $number = sprintf('%s-2026-%03d', $department->code, $index + 1);

                Student::firstOrCreate(
                    ['student_number' => $number],
                    [
                        ...Student::factory()->raw([
                            'department_id' => $department->id,
                            'program_id' => $program->id,
                            'curriculum_id' => $curriculum->id,
                            'year_level_id' => $yearLevels[$index % $yearLevels->count()]->id,
                            'classification' => $scenario['classification'],
                            'status' => $scenario['status'],
                        ]),
                        'student_number' => $number,
                    ]
                );
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\College;
use App\Models\Department;
use App\Models\Program;
use App\Models\Semester;
use Illuminate\Database\Seeder;

class CollegeDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $college = College::firstOrCreate(
            ['code' => 'COA'],
            [
                'name' => 'College of Agriculture',
                'address' => 'Sample State University, Agriculture Campus',
                'contact_email' => 'agriculture@sample-university.edu.ph',
                'contact_phone' => '(000) 000-0000',
            ]
        );

        $departments = [
            [
                'code' => 'CROPSCI',
                'name' => 'Department of Crop Science',
                'programs' => [
                    ['code' => 'BSA-CS', 'name' => 'BS Agriculture', 'major' => 'Crop Science', 'degree_type' => 'Bachelor', 'required_total_units' => 168, 'duration_years' => 4],
                    ['code' => 'BS-HORT', 'name' => 'BS Horticulture', 'major' => null, 'degree_type' => 'Bachelor', 'required_total_units' => 165, 'duration_years' => 4],
                ],
            ],
            [
                'code' => 'ANSCI',
                'name' => 'Department of Animal Science',
                'programs' => [
                    ['code' => 'BSA-AS', 'name' => 'BS Agriculture', 'major' => 'Animal Science', 'degree_type' => 'Bachelor', 'required_total_units' => 168, 'duration_years' => 4],
                ],
            ],
            [
                'code' => 'AGECON',
                'name' => 'Department of Agricultural Economics and Agribusiness Management',
                'programs' => [
                    ['code' => 'BS-AGBM', 'name' => 'BS Agribusiness Management', 'major' => null, 'degree_type' => 'Bachelor', 'required_total_units' => 162, 'duration_years' => 4],
                ],
            ],
            [
                'code' => 'AGENG',
                'name' => 'Department of Agricultural Engineering',
                'programs' => [
                    ['code' => 'BS-AGENG', 'name' => 'BS Agricultural Engineering', 'major' => null, 'degree_type' => 'Bachelor', 'required_total_units' => 180, 'duration_years' => 5],
                ],
            ],
        ];

        foreach ($departments as $departmentData) {
            $department = Department::firstOrCreate(
                ['code' => $departmentData['code']],
                [
                    'college_id' => $college->id,
                    'name' => $departmentData['name'],
                    'office_location' => "{$departmentData['name']} Building",
                ]
            );

            foreach ($departmentData['programs'] as $programData) {
                Program::firstOrCreate(
                    ['code' => $programData['code']],
                    [
                        'department_id' => $department->id,
                        ...$programData,
                    ]
                );
            }
        }

        $previousYear = AcademicYear::firstOrCreate(
            ['start_year' => 2025, 'end_year' => 2026],
            ['is_current' => false]
        );
        $currentYear = AcademicYear::firstOrCreate(
            ['start_year' => 2026, 'end_year' => 2027],
            ['is_current' => true]
        );

        foreach ([$previousYear, $currentYear] as $year) {
            foreach (['FIRST', 'SECOND', 'SUMMER'] as $term) {
                Semester::firstOrCreate(
                    ['academic_year_id' => $year->id, 'term' => $term],
                    ['is_current' => $year->is_current && $term === 'FIRST']
                );
            }
        }
    }
}

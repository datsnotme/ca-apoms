<?php

namespace App\Services\Import;

use App\Enums\StudentClassification;
use App\Enums\StudentStatus;
use App\Models\Curriculum;
use App\Models\Department;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Models\YearLevel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentsImporter implements RowImporter
{
    public function headings(): array
    {
        return [
            'student_number', 'surname', 'first_name', 'middle_name',
            'department_code', 'program_code', 'curriculum_code', 'year_level',
            'classification', 'status',
        ];
    }

    public function sampleRow(): array
    {
        return ['2026-00001', 'Dela Cruz', 'Juan', 'Santos', 'CROPSCI', 'BSA-CS', 'BSA-CS-2026', 1, 'regular', 'active'];
    }

    public function validateRow(array $row): array
    {
        $validator = Validator::make($row, [
            'student_number' => ['required', 'string', 'max:30'],
            'surname' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'department_code' => ['required', 'string', 'exists:departments,code'],
            'program_code' => ['required', 'string', 'exists:programs,code'],
            'curriculum_code' => ['required', 'string', 'exists:curricula,code'],
            'year_level' => ['required', 'integer', 'min:1', 'max:6'],
            'classification' => ['nullable', Rule::enum(StudentClassification::class)],
            'status' => ['nullable', Rule::enum(StudentStatus::class)],
        ]);

        $validated = $validator->validate();

        $department = Department::where('code', $validated['department_code'])->firstOrFail();
        $program = Program::where('code', $validated['program_code'])->where('department_id', $department->id)->first();
        $curriculum = Curriculum::where('code', $validated['curriculum_code'])->first();
        $yearLevel = YearLevel::where('level', $validated['year_level'])->firstOrFail();

        if (! $program) {
            throw ValidationException::withMessages(['program_code' => 'Program does not belong to the given department.']);
        }

        if (! $curriculum || $curriculum->program_id !== $program->id) {
            throw ValidationException::withMessages(['curriculum_code' => 'Curriculum does not belong to the given program.']);
        }

        return [
            'student_number' => $validated['student_number'],
            'surname' => $validated['surname'],
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'department_id' => $department->id,
            'program_id' => $program->id,
            'curriculum_id' => $curriculum->id,
            'year_level_id' => $yearLevel->id,
            'classification' => $validated['classification'] ?? StudentClassification::Regular->value,
            'status' => $validated['status'] ?? StudentStatus::Active->value,
        ];
    }

    public function persistRow(array $data, User $actor): void
    {
        $student = Student::where('student_number', $data['student_number'])->first();

        if ($student) {
            $student->update(collect($data)->except('student_number')->all());
        } else {
            Student::create([...$data, 'created_by' => $actor->id]);
        }
    }
}

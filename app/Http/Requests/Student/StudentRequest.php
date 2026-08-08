<?php

namespace App\Http\Requests\Student;

use App\Enums\RoleName;
use App\Enums\StudentClassification;
use App\Enums\StudentStatus;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student
            ? $this->user()->can('update', $student)
            : $this->user()->can('create', Student::class);
    }

    /**
     * A Department Head can only ever create/update a student within their
     * own department, regardless of what department_id is submitted — the
     * same forcing pattern used by AnnouncementRequest/DocumentRequest/
     * EventRequest/EquipmentRequest/etc. Without this, a crafted request
     * could plant or move a student record into another department.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->user()->hasRole(RoleName::Administrator->value)) {
            $this->merge(['department_id' => $this->user()->department_id]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $studentId = $this->route('student')?->id;

        return [
            'student_number' => ['required', 'string', 'max:30', Rule::unique('students', 'student_number')->ignore($studentId)],
            'surname' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'sex' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'civil_status' => ['nullable', 'string', 'max:30'],
            'citizenship' => ['nullable', 'string', 'max:50'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],

            'department_id' => ['required', 'exists:departments,id'],
            'program_id' => ['required', 'exists:programs,id'],
            'curriculum_id' => ['required', 'exists:curricula,id'],
            'year_level_id' => ['required', 'exists:year_levels,id'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'adviser_id' => ['nullable', 'exists:users,id'],

            'admission_type' => ['nullable', 'string', 'max:50'],
            'date_admitted' => ['nullable', 'date'],
            'expected_graduation_date' => ['nullable', 'date'],
            'scholarship_status' => ['nullable', 'string', 'max:100'],
            'classification' => ['required', Rule::enum(StudentClassification::class)],
            'status' => ['required', Rule::enum(StudentStatus::class)],
            'status_reason' => ['nullable', 'string', 'max:500'],

            'guardian_name' => ['nullable', 'string', 'max:150'],
            'guardian_relationship' => ['nullable', 'string', 'max:50'],
            'guardian_contact_number' => ['nullable', 'string', 'max:30'],
            'guardian_address' => ['nullable', 'string', 'max:255'],

            'emergency_name' => ['nullable', 'string', 'max:150'],
            'emergency_relationship' => ['nullable', 'string', 'max:50'],
            'emergency_contact_number' => ['nullable', 'string', 'max:30'],
            'emergency_address' => ['nullable', 'string', 'max:255'],

            'permanent_address' => ['nullable', 'string', 'max:255'],
            'current_address' => ['nullable', 'string', 'max:255'],
        ];
    }
}

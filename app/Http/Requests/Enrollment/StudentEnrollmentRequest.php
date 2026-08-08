<?php

namespace App\Http\Requests\Enrollment;

use App\Models\StudentEnrollment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', StudentEnrollment::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'exists:students,id',
                Rule::unique('student_enrollments')->where('semester_id', $this->input('semester_id')),
            ],
            'semester_id' => ['required', 'exists:semesters,id'],
        ];
    }
}

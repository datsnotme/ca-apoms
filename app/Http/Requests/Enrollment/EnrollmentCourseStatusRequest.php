<?php

namespace App\Http\Requests\Enrollment;

use App\Enums\EnrollmentCourseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnrollmentCourseStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('studentEnrollment'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(EnrollmentCourseStatus::class)],
        ];
    }
}

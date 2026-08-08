<?php

namespace App\Http\Requests\Graduation;

use App\Enums\GraduationRequirementStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentGraduationRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('requirement'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([GraduationRequirementStatus::Satisfied->value, GraduationRequirementStatus::Waived->value, GraduationRequirementStatus::Pending->value])],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}

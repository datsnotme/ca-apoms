<?php

namespace App\Http\Requests\Faculty;

use App\Enums\FacultyEducationLevel;
use App\Models\FacultyEducation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacultyEducationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $education = $this->route('education');

        return $education
            ? $this->user()->can('update', $education)
            : $this->user()->can('create', FacultyEducation::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'level' => ['required', Rule::in(array_column(FacultyEducationLevel::cases(), 'value'))],
            'degree' => ['required', 'string', 'max:150'],
            'field_of_study' => ['nullable', 'string', 'max:150'],
            'institution' => ['required', 'string', 'max:150'],
            'year_completed' => ['nullable', 'integer', 'min:1950', 'max:'.(date('Y') + 1)],
        ];
    }
}

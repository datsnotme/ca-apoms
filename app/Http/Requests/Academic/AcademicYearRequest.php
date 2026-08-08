<?php

namespace App\Http\Requests\Academic;

use App\Models\AcademicYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        $academicYear = $this->route('academic_year');

        return $academicYear
            ? $this->user()->can('update', $academicYear)
            : $this->user()->can('create', AcademicYear::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $academicYearId = $this->route('academic_year')?->id;

        return [
            'start_year' => [
                'required', 'integer', 'min:2000', 'max:2100',
                Rule::unique('academic_years', 'start_year')->ignore($academicYearId),
            ],
            'end_year' => ['required', 'integer', 'min:2001', 'max:2101'],
            'is_current' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ((int) $this->input('end_year') !== (int) $this->input('start_year') + 1) {
                $validator->errors()->add('end_year', 'End year must be exactly one year after the start year.');
            }
        });
    }
}

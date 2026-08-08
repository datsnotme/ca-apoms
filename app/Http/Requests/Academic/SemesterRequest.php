<?php

namespace App\Http\Requests\Academic;

use App\Enums\SemesterTerm;
use App\Models\Semester;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SemesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $semester = $this->route('semester');

        return $semester
            ? $this->user()->can('update', $semester)
            : $this->user()->can('create', Semester::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $semesterId = $this->route('semester')?->id;

        return [
            'academic_year_id' => [
                'required',
                'exists:academic_years,id',
                Rule::unique('semesters')->where(fn ($q) => $q->where('academic_year_id', $this->input('academic_year_id'))
                    ->where('term', $this->input('term')))
                    ->ignore($semesterId),
            ],
            'term' => ['required', Rule::enum(SemesterTerm::class)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_current' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year_id.unique' => 'This academic year already has a semester for that term.',
        ];
    }
}

<?php

namespace App\Http\Requests\Academic;

use App\Enums\SemesterTerm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurriculumCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('curriculum'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'course_id' => [
                'required',
                'exists:courses,id',
                Rule::unique('curriculum_courses', 'course_id')
                    ->where('curriculum_id', $this->route('curriculum')->id)
                    ->ignore($this->route('curriculumCourse')?->id),
            ],
            'year_level' => ['required', 'integer', 'min:1', 'max:6'],
            'semester' => ['required', Rule::enum(SemesterTerm::class)],
            'is_required' => ['boolean'],
            'units' => ['required', 'numeric', 'min:0', 'max:12'],
            'sequence_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

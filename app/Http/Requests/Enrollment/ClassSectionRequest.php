<?php

namespace App\Http\Requests\Enrollment;

use App\Enums\ClassSectionStatus;
use App\Models\ClassSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $classSection = $this->route('classSection');

        return $classSection
            ? $this->user()->can('update', $classSection)
            : $this->user()->can('create', ClassSection::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $classSectionId = $this->route('classSection')?->id;

        return [
            'course_id' => [
                'required',
                'exists:courses,id',
                Rule::unique('class_sections')
                    ->where('semester_id', $this->input('semester_id'))
                    ->where('section_label', $this->input('section_label'))
                    ->ignore($classSectionId),
            ],
            'semester_id' => ['required', 'exists:semesters,id'],
            'section_label' => ['required', 'string', 'max:20'],
            'max_students' => ['required', 'integer', 'min:1', 'max:200'],
            'status' => ['required', Rule::enum(ClassSectionStatus::class)],
            'faculty_id' => ['nullable', 'exists:users,id'],
        ];
    }
}

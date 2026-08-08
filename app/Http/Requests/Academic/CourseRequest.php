<?php

namespace App\Http\Requests\Academic;

use App\Enums\CourseCategory;
use App\Enums\RoleName;
use App\Enums\SemesterTerm;
use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        return $course
            ? $this->user()->can('update', $course)
            : $this->user()->can('create', Course::class);
    }

    /**
     * `courses.manage` is Administrator-only today, so this is a no-op in
     * practice — kept for consistency/future-proofing with every other
     * department_id-bearing request (AnnouncementRequest, StudentRequest,
     * etc.) in case courses.manage is ever delegated to Department Head.
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
        $courseId = $this->route('course')?->id;

        return [
            'department_id' => ['required', 'exists:departments,id'],
            'code' => ['required', 'string', 'max:20', Rule::unique('courses', 'code')->ignore($courseId)],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'units' => ['required', 'numeric', 'min:0', 'max:12'],
            'lecture_hours' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'laboratory_hours' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'category' => ['required', Rule::enum(CourseCategory::class)],
            'recommended_year_level' => ['nullable', 'integer', 'min:1', 'max:6'],
            'recommended_semester' => ['nullable', Rule::enum(SemesterTerm::class)],
            'is_active' => ['boolean'],
            'prerequisite_ids' => ['array'],
            'prerequisite_ids.*' => ['integer', 'exists:courses,id'],
            'corequisite_ids' => ['array'],
            'corequisite_ids.*' => ['integer', 'exists:courses,id'],
        ];
    }
}

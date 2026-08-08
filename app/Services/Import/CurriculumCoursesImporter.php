<?php

namespace App\Services\Import;

use App\Enums\SemesterTerm;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CurriculumCoursesImporter implements RowImporter
{
    public function headings(): array
    {
        return ['curriculum_code', 'course_code', 'year_level', 'semester', 'is_required', 'units', 'sequence_order'];
    }

    public function sampleRow(): array
    {
        return ['BSA-CS-2026', 'AGRO101', 1, 'FIRST', 1, 3, 1];
    }

    public function validateRow(array $row): array
    {
        $validated = Validator::make($row, [
            'curriculum_code' => ['required', 'string', 'exists:curricula,code'],
            'course_code' => ['required', 'string', 'exists:courses,code'],
            'year_level' => ['required', 'integer', 'min:1', 'max:6'],
            'semester' => ['required', Rule::enum(SemesterTerm::class)],
            'is_required' => ['nullable', 'boolean'],
            'units' => ['required', 'numeric', 'min:0', 'max:12'],
            'sequence_order' => ['nullable', 'integer', 'min:0'],
        ])->validate();

        $curriculum = Curriculum::where('code', $validated['curriculum_code'])->firstOrFail();
        $course = Course::where('code', $validated['course_code'])->firstOrFail();

        return [
            'curriculum_id' => $curriculum->id,
            'course_id' => $course->id,
            'year_level' => $validated['year_level'],
            'semester' => $validated['semester'],
            'is_required' => array_key_exists('is_required', $validated) ? (bool) $validated['is_required'] : true,
            'units' => $validated['units'],
            'sequence_order' => $validated['sequence_order'] ?? 0,
        ];
    }

    public function persistRow(array $data, User $actor): void
    {
        CurriculumCourse::updateOrCreate(
            ['curriculum_id' => $data['curriculum_id'], 'course_id' => $data['course_id']],
            collect($data)->except(['curriculum_id', 'course_id'])->all()
        );
    }
}

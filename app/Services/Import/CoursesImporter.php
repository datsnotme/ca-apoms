<?php

namespace App\Services\Import;

use App\Enums\CourseCategory;
use App\Models\Course;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CoursesImporter implements RowImporter
{
    public function headings(): array
    {
        return ['department_code', 'code', 'title', 'units', 'category', 'lecture_hours', 'laboratory_hours', 'is_active'];
    }

    public function sampleRow(): array
    {
        return ['CROPSCI', 'AGRO101', 'Introduction to Crop Science', 3, 'crop_science', 3, 0, 1];
    }

    public function validateRow(array $row): array
    {
        $validated = Validator::make($row, [
            'department_code' => ['required', 'string', 'exists:departments,code'],
            'code' => ['required', 'string', 'max:20'],
            'title' => ['required', 'string', 'max:255'],
            'units' => ['required', 'numeric', 'min:0', 'max:12'],
            'category' => ['required', Rule::enum(CourseCategory::class)],
            'lecture_hours' => ['nullable', 'numeric', 'min:0', 'max:12'],
            'laboratory_hours' => ['nullable', 'numeric', 'min:0', 'max:12'],
            'is_active' => ['nullable', 'boolean'],
        ])->validate();

        $department = Department::where('code', $validated['department_code'])->firstOrFail();

        return [
            'department_id' => $department->id,
            'code' => strtoupper($validated['code']),
            'title' => $validated['title'],
            'units' => $validated['units'],
            'category' => $validated['category'],
            'lecture_hours' => $validated['lecture_hours'] ?? 0,
            'laboratory_hours' => $validated['laboratory_hours'] ?? 0,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
        ];
    }

    public function persistRow(array $data, User $actor): void
    {
        Course::updateOrCreate(['code' => $data['code']], collect($data)->except('code')->all());
    }
}

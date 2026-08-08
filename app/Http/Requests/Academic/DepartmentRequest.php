<?php

namespace App\Http\Requests\Academic;

use App\Enums\ActiveStatus;
use App\Models\College;
use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $department = $this->route('department');

        return $department
            ? $this->user()->can('update', $department)
            : $this->user()->can('create', Department::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $departmentId = $this->route('department')?->id;

        return [
            'college_id' => ['required', 'exists:colleges,id'],
            'code' => ['required', 'string', 'max:20', Rule::unique('departments', 'code')->ignore($departmentId)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'department_head_id' => ['nullable', 'exists:users,id'],
            'office_location' => ['nullable', 'string', 'max:150'],
            'contact_info' => ['nullable', 'string', 'max:150'],
            'status' => ['required', Rule::enum(ActiveStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('college_id')) {
            $this->merge(['college_id' => College::query()->value('id')]);
        }
    }
}

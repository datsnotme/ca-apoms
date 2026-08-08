<?php

namespace App\Http\Requests\Academic;

use App\Enums\ActiveStatus;
use App\Enums\RoleName;
use App\Models\Program;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        $program = $this->route('program');

        return $program
            ? $this->user()->can('update', $program)
            : $this->user()->can('create', Program::class);
    }

    /**
     * `programs.manage` is Administrator-only today, so this is a no-op in
     * practice — kept for consistency/future-proofing with every other
     * department_id-bearing request (AnnouncementRequest, StudentRequest,
     * etc.) in case programs.manage is ever delegated to Department Head.
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
        $programId = $this->route('program')?->id;

        return [
            'department_id' => ['required', 'exists:departments,id'],
            'code' => ['required', 'string', 'max:20', Rule::unique('programs', 'code')->ignore($programId)],
            'name' => ['required', 'string', 'max:150'],
            'degree_type' => ['nullable', 'string', 'max:50'],
            'major' => ['nullable', 'string', 'max:100'],
            'required_total_units' => ['nullable', 'integer', 'min:0', 'max:400'],
            'duration_years' => ['nullable', 'integer', 'min:1', 'max:8'],
            'status' => ['required', Rule::enum(ActiveStatus::class)],
        ];
    }
}

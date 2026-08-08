<?php

namespace App\Http\Requests\Research;

use App\Enums\ResearchProjectStatus;
use App\Enums\RoleName;
use App\Models\ResearchProject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResearchProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('researchProject');

        return $project
            ? $this->user()->can('update', $project)
            : $this->user()->can('create', ResearchProject::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['sometimes', Rule::enum(ResearchProjectStatus::class)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'funding_source' => ['nullable', 'string', 'max:150'],
            'department_id' => ['required_if:is_admin,true', 'exists:departments,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // A research project always belongs to exactly one department. An
        // Administrator (who has none) must pick one explicitly; everyone
        // else's project is always their own department, regardless of
        // input — the same field-forcing pattern used throughout Phase 6.
        $isAdmin = $this->user()->hasRole(RoleName::Administrator->value);

        $this->merge(['is_admin' => $isAdmin]);

        if (! $isAdmin) {
            $this->merge(['department_id' => $this->user()->department_id]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        unset($data['is_admin']);

        return $data;
    }
}

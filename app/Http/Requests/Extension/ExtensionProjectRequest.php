<?php

namespace App\Http\Requests\Extension;

use App\Enums\ExtensionProjectStatus;
use App\Enums\RoleName;
use App\Models\ExtensionProject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExtensionProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('extensionProject');

        return $project
            ? $this->user()->can('update', $project)
            : $this->user()->can('create', ExtensionProject::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['sometimes', Rule::enum(ExtensionProjectStatus::class)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'funding_source' => ['nullable', 'string', 'max:150'],
            'department_id' => ['required_if:is_admin,true', 'exists:departments,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Same field-forcing pattern as ResearchProjectRequest (Phase 7A):
        // only an Admin (who has none) picks a department explicitly;
        // everyone else's project is always their own department.
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

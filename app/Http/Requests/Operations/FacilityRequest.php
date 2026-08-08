<?php

namespace App\Http\Requests\Operations;

use App\Enums\RoleName;
use App\Models\Facility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $facility = $this->route('facility');

        return $facility
            ? $this->user()->can('update', $facility)
            : $this->user()->can('create', Facility::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $facility = $this->route('facility');

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('facilities', 'name')->ignore($facility)],
            'type' => ['required', 'string', 'max:100'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'location' => ['nullable', 'string', 'max:150'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Only an Admin may register a shared/college-wide facility or
        // assign a department other than their own; a Department Head's
        // facilities are always scoped to their own department, regardless
        // of input — same field-forcing pattern as AnnouncementRequest.
        if (! $this->user()->hasRole(RoleName::Administrator->value)) {
            $this->merge(['department_id' => $this->user()->department_id]);
        }
    }
}

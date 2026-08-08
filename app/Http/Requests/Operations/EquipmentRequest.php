<?php

namespace App\Http\Requests\Operations;

use App\Enums\EquipmentStatus;
use App\Enums\RoleName;
use App\Models\Equipment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $equipment = $this->route('equipment');

        return $equipment
            ? $this->user()->can('update', $equipment)
            : $this->user()->can('create', Equipment::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $equipment = $this->route('equipment');

        return [
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'string', 'max:100'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'facility_id' => ['nullable', 'exists:facilities,id'],
            'serial_number' => ['nullable', 'string', 'max:100', Rule::unique('equipment', 'serial_number')->ignore($equipment)],
            'status' => ['sometimes', Rule::enum(EquipmentStatus::class)],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Same field-forcing pattern as FacilityRequest (Phase 7C): only an
        // Admin may register shared equipment or assign a department other
        // than their own.
        if (! $this->user()->hasRole(RoleName::Administrator->value)) {
            $this->merge(['department_id' => $this->user()->department_id]);
        }
    }
}

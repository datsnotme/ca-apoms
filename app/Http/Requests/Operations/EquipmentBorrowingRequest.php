<?php

namespace App\Http\Requests\Operations;

use App\Enums\EquipmentStatus;
use App\Models\Equipment;
use App\Models\EquipmentBorrowing;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class EquipmentBorrowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [EquipmentBorrowing::class, $this->route('equipment')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'borrowed_by' => ['required', 'exists:users,id'],
            'expected_return_at' => ['nullable', 'date'],
            'purpose' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        /** @var Equipment $equipment */
        $equipment = $this->route('equipment');

        $validator->after(function (Validator $validator) use ($equipment) {
            if ($equipment->status !== EquipmentStatus::Available) {
                $validator->errors()->add('equipment', 'This equipment is not currently available to borrow.');
            }
        });
    }
}

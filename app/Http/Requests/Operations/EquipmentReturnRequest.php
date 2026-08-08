<?php

namespace App\Http\Requests\Operations;

use App\Models\EquipmentReturn;
use Illuminate\Foundation\Http\FormRequest;

class EquipmentReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [EquipmentReturn::class, $this->route('equipment')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'condition_on_return' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

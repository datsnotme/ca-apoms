<?php

namespace App\Http\Requests\Extension;

use App\Models\ExtensionBeneficiary;
use Illuminate\Foundation\Http\FormRequest;

class ExtensionBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('extensionProject');

        return $this->user()->can('create', [ExtensionBeneficiary::class, $project]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'beneficiary_name' => ['required', 'string', 'max:150'],
            'beneficiary_type' => ['required', 'string', 'max:100'],
            'count' => ['nullable', 'integer', 'min:0'],
            'location' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

<?php

namespace App\Http\Requests\Faculty;

use App\Models\FacultyCredential;
use Illuminate\Foundation\Http\FormRequest;

class FacultyCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $credential = $this->route('credential');

        return $credential
            ? $this->user()->can('update', $credential)
            : $this->user()->can('create', FacultyCredential::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'issuing_body' => ['nullable', 'string', 'max:150'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'issued_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issued_date'],
        ];
    }
}

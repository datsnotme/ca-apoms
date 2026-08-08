<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Self-service profile editing. Email, username, employee number, department,
 * role, and status are intentionally excluded — those require administrator
 * action via User Management, matching the spec's "selected fields may
 * require administrator verification" pattern (see section 5.12).
 */
class ProfileUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'surname' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'contact_number' => ['nullable', 'string', 'max:30'],
        ];
    }
}

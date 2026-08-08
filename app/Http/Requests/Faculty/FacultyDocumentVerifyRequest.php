<?php

namespace App\Http\Requests\Faculty;

use App\Enums\DocumentVerificationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacultyDocumentVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('verify', $this->route('document'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'verification_status' => ['required', Rule::in([
                DocumentVerificationStatus::Verified->value,
                DocumentVerificationStatus::Rejected->value,
            ])],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}

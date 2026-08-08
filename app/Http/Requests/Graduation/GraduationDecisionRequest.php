<?php

namespace App\Http\Requests\Graduation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GraduationDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('decide', $this->route('graduationCandidate'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

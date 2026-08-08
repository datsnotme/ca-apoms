<?php

namespace App\Http\Requests\Operations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InternalRequestReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('internalRequest'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

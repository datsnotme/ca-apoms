<?php

namespace App\Http\Requests\Operations;

use App\Models\InternalRequest;
use Illuminate\Foundation\Http\FormRequest;

class InternalRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', InternalRequest::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:5000'],
        ];
    }
}

<?php

namespace App\Http\Requests\Operations;

use App\Models\DocumentCategory;
use Illuminate\Foundation\Http\FormRequest;

class DocumentCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', DocumentCategory::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:document_categories,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}

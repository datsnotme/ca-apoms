<?php

namespace App\Http\Requests\Operations;

use App\Enums\RoleName;
use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;

class DocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('document');

        return $document
            ? $this->user()->can('update', $document)
            : $this->user()->can('create', Document::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Creating a document uploads its first version in the same
        // request; editing only ever touches metadata, never the file.
        $isCreate = ! $this->route('document');

        return [
            'document_category_id' => ['required', 'exists:document_categories,id'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'file' => [$isCreate ? 'required' : 'nullable', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Only an Admin may file a college-wide document or assign a
        // department other than their own; a Department Head's documents
        // are always scoped to their own department, regardless of input.
        if (! $this->user()->hasRole(RoleName::Administrator->value)) {
            $this->merge(['department_id' => $this->user()->department_id]);
        }
    }
}

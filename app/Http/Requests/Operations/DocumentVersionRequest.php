<?php

namespace App\Http\Requests\Operations;

use App\Models\DocumentVersion;
use Illuminate\Foundation\Http\FormRequest;

class DocumentVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [DocumentVersion::class, $this->route('document')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

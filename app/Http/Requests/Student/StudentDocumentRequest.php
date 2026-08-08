<?php

namespace App\Http\Requests\Student;

use App\Enums\DocumentCategory;
use App\Models\StudentDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('upload', [StudentDocument::class, $this->route('student')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(DocumentCategory::class)],
            'title' => ['required', 'string', 'max:150'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ];
    }
}

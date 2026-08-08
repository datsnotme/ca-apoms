<?php

namespace App\Http\Requests\Faculty;

use App\Enums\FacultyDocumentCategory;
use App\Models\FacultyDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacultyDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('upload', [FacultyDocument::class, $this->route('user')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(FacultyDocumentCategory::class)],
            'title' => ['required', 'string', 'max:150'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ];
    }
}

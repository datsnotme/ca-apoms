<?php

namespace App\Http\Requests\Graduation;

use App\Models\GraduationRequirementTemplate;
use Illuminate\Foundation\Http\FormRequest;

class GraduationRequirementTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = $this->route('graduation_requirement_template');

        return $template
            ? $this->user()->can('update', $template)
            : $this->user()->can('create', GraduationRequirementTemplate::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'program_id' => ['nullable', 'exists:programs,id'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_required' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

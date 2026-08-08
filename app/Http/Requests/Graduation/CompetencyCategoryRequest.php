<?php

namespace App\Http\Requests\Graduation;

use App\Models\CompetencyCategory;
use Illuminate\Foundation\Http\FormRequest;

class CompetencyCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('competency_category');

        return $category
            ? $this->user()->can('update', $category)
            : $this->user()->can('create', CompetencyCategory::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

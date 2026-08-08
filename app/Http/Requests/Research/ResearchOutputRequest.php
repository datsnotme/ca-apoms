<?php

namespace App\Http\Requests\Research;

use App\Models\ResearchOutput;
use Illuminate\Foundation\Http\FormRequest;

class ResearchOutputRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('researchProject');

        return $this->user()->can('create', [ResearchOutput::class, $project]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'type' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'output_date' => ['nullable', 'date'],
            'reference_url' => ['nullable', 'string', 'max:255'],
        ];
    }
}

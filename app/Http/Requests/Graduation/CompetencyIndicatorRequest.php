<?php

namespace App\Http\Requests\Graduation;

use App\Models\CompetencyIndicator;
use Illuminate\Foundation\Http\FormRequest;

class CompetencyIndicatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $indicator = $this->route('indicator');

        return $indicator
            ? $this->user()->can('update', $indicator)
            : $this->user()->can('create', CompetencyIndicator::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

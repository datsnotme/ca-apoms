<?php

namespace App\Http\Requests\Graduation;

use Illuminate\Foundation\Http\FormRequest;

class GraduationRecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('recommend', $this->route('graduationCandidate'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

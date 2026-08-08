<?php

namespace App\Http\Requests\Graduation;

use App\Models\GraduationCandidate;
use Illuminate\Foundation\Http\FormRequest;

class CompetencyRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var GraduationCandidate $candidate */
        $candidate = $this->route('graduationCandidate');

        return $this->user()->can('graduation.evaluate')
            && $candidate->competencyEvaluators()->where('evaluator_id', $this->user()->id)->exists();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

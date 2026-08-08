<?php

namespace App\Http\Requests\Grading;

use App\Models\GradingScale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradeCorrectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reviewGrades', $this->route('classSection'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'grade' => ['required', Rule::in(GradingScale::default()->values->pluck('value'))],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}

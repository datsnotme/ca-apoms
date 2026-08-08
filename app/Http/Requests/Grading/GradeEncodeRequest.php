<?php

namespace App\Http\Requests\Grading;

use App\Models\GradingScale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradeEncodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('encodeGrades', $this->route('classSection'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'grade' => ['nullable', Rule::in(GradingScale::default()->values->pluck('value'))],
        ];
    }
}

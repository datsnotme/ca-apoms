<?php

namespace App\Http\Requests\Grading;

use Illuminate\Foundation\Http\FormRequest;

class GradeReturnRequest extends FormRequest
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
            'remarks' => ['required', 'string', 'max:1000'],
        ];
    }
}

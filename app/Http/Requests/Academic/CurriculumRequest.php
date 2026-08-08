<?php

namespace App\Http\Requests\Academic;

use App\Enums\ActiveStatus;
use App\Models\Curriculum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurriculumRequest extends FormRequest
{
    public function authorize(): bool
    {
        $curriculum = $this->route('curriculum');

        return $curriculum
            ? $this->user()->can('update', $curriculum)
            : $this->user()->can('create', Curriculum::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $curriculumId = $this->route('curriculum')?->id;

        return [
            'program_id' => ['required', 'exists:programs,id'],
            'effective_academic_year_id' => ['required', 'exists:academic_years,id'],
            'code' => ['required', 'string', 'max:30', Rule::unique('curricula', 'code')->ignore($curriculumId)],
            'name' => ['required', 'string', 'max:150'],
            'required_total_units' => ['nullable', 'integer', 'min:0', 'max:400'],
            'status' => ['required', Rule::enum(ActiveStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

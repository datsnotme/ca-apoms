<?php

namespace App\Http\Requests\Faculty;

use App\Models\FacultyTraining;
use Illuminate\Foundation\Http\FormRequest;

class FacultyTrainingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $training = $this->route('training');

        return $training
            ? $this->user()->can('update', $training)
            : $this->user()->can('create', FacultyTraining::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'provider' => ['nullable', 'string', 'max:150'],
            'training_type' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'hours' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }
}

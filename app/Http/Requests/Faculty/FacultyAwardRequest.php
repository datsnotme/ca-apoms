<?php

namespace App\Http\Requests\Faculty;

use App\Models\FacultyAward;
use Illuminate\Foundation\Http\FormRequest;

class FacultyAwardRequest extends FormRequest
{
    public function authorize(): bool
    {
        $award = $this->route('award');

        return $award
            ? $this->user()->can('update', $award)
            : $this->user()->can('create', FacultyAward::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'awarding_body' => ['nullable', 'string', 'max:150'],
            'date_awarded' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

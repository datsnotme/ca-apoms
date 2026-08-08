<?php

namespace App\Http\Requests\Student;

use App\Models\StudentAdvisingRecord;
use Illuminate\Foundation\Http\FormRequest;

class StudentAdvisingRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('record');

        return $record
            ? $this->user()->can('update', $record)
            : $this->user()->can('create', [StudentAdvisingRecord::class, $this->route('student')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'semester_id' => ['required', 'exists:semesters,id'],
            'session_date' => ['required', 'date'],
            'summary' => ['required', 'string', 'max:5000'],
            'recommendations' => ['nullable', 'string', 'max:5000'],
            'follow_up_required' => ['boolean'],
        ];
    }
}

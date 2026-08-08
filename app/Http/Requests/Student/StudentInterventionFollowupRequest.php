<?php

namespace App\Http\Requests\Student;

use App\Enums\InterventionStatus;
use App\Models\StudentInterventionFollowup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentInterventionFollowupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $followup = $this->route('followup');

        return $followup
            ? $this->user()->can('update', $followup)
            : $this->user()->can('create', [StudentInterventionFollowup::class, $this->route('student')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Creating a follow-up always needs a description. Updating one
        // doesn't — the quick status-change controls (Start/Complete/
        // Cancel) only send `status`, a partial update, not a full edit.
        $isUpdate = (bool) $this->route('followup');

        return [
            'description' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:2000'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'student_advising_record_id' => ['nullable', 'exists:student_advising_records,id'],
            'status' => ['sometimes', Rule::enum(InterventionStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

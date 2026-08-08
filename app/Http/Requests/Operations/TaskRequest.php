<?php

namespace App\Http\Requests\Operations;

use App\Enums\ActionItemStatus;
use App\Enums\RoleName;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task
            ? $this->user()->can('update', $task)
            : $this->user()->can('create', Task::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $task = $this->route('task');

        // The creator (or an Admin) may edit every field. An assignee who
        // is neither may only change status/notes — enforced here, not just
        // hidden in the UI, the same field-level split FacultyProfileRequest
        // (Phase 5A) uses for admin-only vs self-editable fields.
        $isFullEditor = ! $task
            || $this->user()->hasRole(RoleName::Administrator->value)
            || $task->created_by === $this->user()->id;

        if (! $isFullEditor) {
            return [
                'status' => ['sometimes', Rule::enum(ActionItemStatus::class)],
                'notes' => ['nullable', 'string', 'max:2000'],
            ];
        }

        return [
            'title' => [$task ? 'sometimes' : 'required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::enum(ActionItemStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

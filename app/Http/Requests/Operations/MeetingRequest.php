<?php

namespace App\Http\Requests\Operations;

use App\Enums\RoleName;
use App\Models\Meeting;
use Illuminate\Foundation\Http\FormRequest;

class MeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $meeting = $this->route('meeting');

        return $meeting
            ? $this->user()->can('update', $meeting)
            : $this->user()->can('create', Meeting::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'location' => ['nullable', 'string', 'max:150'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Only an Admin may schedule a college-wide meeting or assign a
        // department other than their own; a Department Head's meetings are
        // always scoped to their own department, regardless of input.
        if (! $this->user()->hasRole(RoleName::Administrator->value)) {
            $this->merge(['department_id' => $this->user()->department_id]);
        }
    }
}

<?php

namespace App\Http\Requests\Operations;

use App\Enums\RoleName;
use App\Models\Announcement;
use Illuminate\Foundation\Http\FormRequest;

class AnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $announcement = $this->route('announcement');

        return $announcement
            ? $this->user()->can('update', $announcement)
            : $this->user()->can('create', Announcement::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Only an Admin may post a college-wide announcement or assign a
        // department other than their own; a Department Head's announcements
        // are always scoped to their own department, regardless of input.
        if (! $this->user()->hasRole(RoleName::Administrator->value)) {
            $this->merge(['department_id' => $this->user()->department_id]);
        }
    }
}

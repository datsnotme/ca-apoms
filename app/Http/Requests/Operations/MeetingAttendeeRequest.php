<?php

namespace App\Http\Requests\Operations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MeetingAttendeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('meeting'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $meeting = $this->route('meeting');

        return [
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($q) => $q->whereNull('deleted_at')),
                Rule::unique('meeting_attendees', 'user_id')->where('meeting_id', $meeting->id),
            ],
        ];
    }
}

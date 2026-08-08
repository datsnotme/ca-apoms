<?php

namespace App\Http\Requests\Operations;

use App\Enums\ActionItemStatus;
use App\Models\MeetingActionItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MeetingActionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actionItem = $this->route('actionItem');

        return $actionItem
            ? $this->user()->can('update', $actionItem)
            : $this->user()->can('create', [MeetingActionItem::class, $this->route('meeting')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Creating an action item always needs a description. Updating one
        // doesn't — the quick status-change controls only send `status`, a
        // partial update, not a full edit.
        $isUpdate = (bool) $this->route('actionItem');

        return [
            'description' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:2000'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::enum(ActionItemStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

<?php

namespace App\Http\Controllers\Operations;

use App\Enums\ActionItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\MeetingActionItemRequest;
use App\Models\Meeting;
use App\Models\MeetingActionItem;
use Illuminate\Http\RedirectResponse;

class MeetingActionItemController extends Controller
{
    public function store(MeetingActionItemRequest $request, Meeting $meeting): RedirectResponse
    {
        $meeting->actionItems()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Action item added.');
    }

    public function update(MeetingActionItemRequest $request, Meeting $meeting, MeetingActionItem $actionItem): RedirectResponse
    {
        abort_unless($actionItem->meeting_id === $meeting->id, 404);

        $data = $request->validated();

        if (($data['status'] ?? null) === ActionItemStatus::Completed->value && $actionItem->status !== ActionItemStatus::Completed) {
            $data['completed_by'] = $request->user()->id;
            $data['completed_at'] = now();
        } elseif (isset($data['status']) && $data['status'] !== ActionItemStatus::Completed->value) {
            $data['completed_by'] = null;
            $data['completed_at'] = null;
        }

        $actionItem->update($data);

        return back()->with('success', 'Action item updated.');
    }

    public function destroy(Meeting $meeting, MeetingActionItem $actionItem): RedirectResponse
    {
        abort_unless($actionItem->meeting_id === $meeting->id, 404);

        $this->authorize('delete', $actionItem);

        $actionItem->delete();

        return back()->with('success', 'Action item removed.');
    }
}

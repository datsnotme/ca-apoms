<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\MeetingAttendeeRequest;
use App\Models\Meeting;
use App\Models\MeetingAttendee;
use App\Models\User;
use App\Notifications\MeetingInvitationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MeetingAttendeeController extends Controller
{
    public function store(MeetingAttendeeRequest $request, Meeting $meeting): RedirectResponse
    {
        $attendee = $meeting->attendees()->create([
            ...$request->validated(),
            'invited_by' => $request->user()->id,
        ]);

        User::find($attendee->user_id)?->notify(new MeetingInvitationNotification($meeting));

        return back()->with('success', 'Attendee added.');
    }

    public function update(Request $request, Meeting $meeting, MeetingAttendee $attendee): RedirectResponse
    {
        $this->authorize('update', $meeting);
        abort_unless($attendee->meeting_id === $meeting->id, 404);

        $attended = $request->boolean('attended');

        $attendee->update([
            'attended' => $attended,
            'attended_at' => $attended ? now() : null,
        ]);

        return back()->with('success', 'Attendance updated.');
    }

    public function destroy(Meeting $meeting, MeetingAttendee $attendee): RedirectResponse
    {
        $this->authorize('update', $meeting);
        abort_unless($attendee->meeting_id === $meeting->id, 404);

        $attendee->delete();

        return back()->with('success', 'Attendee removed.');
    }
}

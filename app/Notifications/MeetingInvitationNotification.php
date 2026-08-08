<?php

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MeetingInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Meeting $meeting) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Meeting Invitation',
            'message' => $this->meeting->title,
            'url' => route('meetings.show', $this->meeting),
        ];
    }
}

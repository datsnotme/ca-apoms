<?php

namespace App\Notifications;

use App\Models\InternalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InternalRequestStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly InternalRequest $request) {}

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
            'title' => 'Request '.$this->request->status->label(),
            'message' => $this->request->title,
            'url' => route('internal-requests.index'),
        ];
    }
}

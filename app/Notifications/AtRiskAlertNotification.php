<?php

namespace App\Notifications;

use App\Models\ProgressAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AtRiskAlertNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ProgressAlert $alert) {}

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
            'title' => 'At-Risk Student Alert',
            'message' => "{$this->alert->student->name}: {$this->alert->message}",
            'url' => route('academic-progress.index'),
        ];
    }
}

<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Models\MeetingActionItem;
use App\Models\User;

class MeetingActionItemPolicy
{
    public function create(User $user, Meeting $meeting): bool
    {
        return app(MeetingPolicy::class)->update($user, $meeting);
    }

    public function update(User $user, MeetingActionItem $actionItem): bool
    {
        if ($actionItem->assigned_to === $user->id) {
            return true;
        }

        return app(MeetingPolicy::class)->update($user, $actionItem->meeting);
    }

    public function delete(User $user, MeetingActionItem $actionItem): bool
    {
        return $this->update($user, $actionItem);
    }
}

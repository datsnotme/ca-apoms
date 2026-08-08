<?php

namespace App\Enums;

/**
 * Generic trackable-work-item lifecycle, first used by MeetingActionItem
 * (Phase 6B) and intended for reuse by Task (Phase 6C) rather than minting
 * a near-identical enum per module — see ASSUMPTIONS.md.
 */
enum ActionItemStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }
}

<?php

namespace App\Enums;

/**
 * Computed (not persisted) status of one curriculum course on a student's
 * progress checklist. See ProgressComputationService.
 */
enum CourseChecklistStatus: string
{
    case Completed = 'completed';
    case Failed = 'failed';
    case Incomplete = 'incomplete';
    case InProgress = 'in_progress';
    case Dropped = 'dropped';
    case NotTaken = 'not_taken';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Incomplete => 'Incomplete',
            self::InProgress => 'In Progress',
            self::Dropped => 'Dropped',
            self::NotTaken => 'Not Taken',
            self::Pending => 'Pending',
        };
    }
}

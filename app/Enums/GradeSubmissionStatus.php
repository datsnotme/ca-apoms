<?php

namespace App\Enums;

enum GradeSubmissionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Returned = 'returned';
    case Reviewed = 'reviewed';
    case Finalized = 'finalized';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted for Review',
            self::Returned => 'Returned for Correction',
            self::Reviewed => 'Reviewed',
            self::Finalized => 'Finalized',
        };
    }
}

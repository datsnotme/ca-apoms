<?php

namespace App\Enums;

enum StudentGradeStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Reviewed = 'reviewed';
    case Finalized = 'finalized';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Reviewed => 'Reviewed',
            self::Finalized => 'Finalized',
            self::Locked => 'Locked',
        };
    }
}

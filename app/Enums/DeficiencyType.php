<?php

namespace App\Enums;

enum DeficiencyType: string
{
    case Failed = 'failed';
    case Incomplete = 'incomplete';
    case NotTaken = 'not_taken';
    case Dropped = 'dropped';

    public function label(): string
    {
        return match ($this) {
            self::Failed => 'Failed',
            self::Incomplete => 'Incomplete',
            self::NotTaken => 'Not Taken',
            self::Dropped => 'Dropped',
        };
    }
}

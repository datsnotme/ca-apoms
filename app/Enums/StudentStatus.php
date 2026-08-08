<?php

namespace App\Enums;

enum StudentStatus: string
{
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Withdrawn = 'withdrawn';
    case Dropped = 'dropped';
    case Inactive = 'inactive';
    case Graduated = 'graduated';
    case Transferred = 'transferred';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::OnLeave => 'On Leave',
            self::Withdrawn => 'Withdrawn',
            self::Dropped => 'Dropped',
            self::Inactive => 'Inactive',
            self::Graduated => 'Graduated',
            self::Transferred => 'Transferred',
            self::Archived => 'Archived',
        };
    }
}

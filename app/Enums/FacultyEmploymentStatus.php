<?php

namespace App\Enums;

enum FacultyEmploymentStatus: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Visiting = 'visiting';
    case OnLeave = 'on_leave';

    public function label(): string
    {
        return match ($this) {
            self::FullTime => 'Full-Time',
            self::PartTime => 'Part-Time',
            self::Visiting => 'Visiting',
            self::OnLeave => 'On Leave',
        };
    }
}

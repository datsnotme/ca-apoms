<?php

namespace App\Enums;

enum SemesterTerm: string
{
    case First = 'FIRST';
    case Second = 'SECOND';
    case Summer = 'SUMMER';

    public function label(): string
    {
        return match ($this) {
            self::First => '1st Semester',
            self::Second => '2nd Semester',
            self::Summer => 'Summer',
        };
    }
}

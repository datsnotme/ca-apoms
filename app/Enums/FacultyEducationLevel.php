<?php

namespace App\Enums;

enum FacultyEducationLevel: string
{
    case Bachelors = 'bachelors';
    case Masters = 'masters';
    case Doctorate = 'doctorate';

    public function label(): string
    {
        return match ($this) {
            self::Bachelors => "Bachelor's",
            self::Masters => "Master's",
            self::Doctorate => 'Doctorate',
        };
    }
}

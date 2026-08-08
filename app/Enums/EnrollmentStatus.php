<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Enrolled = 'enrolled';
    case Withdrawn = 'withdrawn';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Enrolled => 'Enrolled',
            self::Withdrawn => 'Withdrawn',
            self::Completed => 'Completed',
        };
    }
}

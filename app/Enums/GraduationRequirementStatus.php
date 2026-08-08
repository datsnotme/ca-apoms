<?php

namespace App\Enums;

enum GraduationRequirementStatus: string
{
    case Pending = 'pending';
    case Satisfied = 'satisfied';
    case Waived = 'waived';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Satisfied => 'Satisfied',
            self::Waived => 'Waived',
        };
    }
}

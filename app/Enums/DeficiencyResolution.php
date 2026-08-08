<?php

namespace App\Enums;

enum DeficiencyResolution: string
{
    case Retake = 'retake';
    case Substitution = 'substitution';
    case Waiver = 'waiver';

    public function label(): string
    {
        return match ($this) {
            self::Retake => 'Retake',
            self::Substitution => 'Substitution',
            self::Waiver => 'Waiver',
        };
    }
}

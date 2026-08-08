<?php

namespace App\Enums;

enum StudentClassification: string
{
    case Regular = 'regular';
    case Irregular = 'irregular';
    case Transferee = 'transferee';
    case Shiftee = 'shiftee';
    case Returning = 'returning';
    case Graduating = 'graduating';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Regular',
            self::Irregular => 'Irregular',
            self::Transferee => 'Transferee',
            self::Shiftee => 'Shiftee',
            self::Returning => 'Returning',
            self::Graduating => 'Graduating',
        };
    }
}

<?php

namespace App\Enums;

enum ExtensionProjectStatus: string
{
    case Proposed = 'proposed';
    case Ongoing = 'ongoing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Proposed => 'Proposed',
            self::Ongoing => 'Ongoing',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }
}

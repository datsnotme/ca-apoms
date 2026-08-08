<?php

namespace App\Enums;

enum EquipmentStatus: string
{
    case Available = 'available';
    case Borrowed = 'borrowed';
    case UnderMaintenance = 'under_maintenance';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Borrowed => 'Borrowed',
            self::UnderMaintenance => 'Under Maintenance',
            self::Retired => 'Retired',
        };
    }
}

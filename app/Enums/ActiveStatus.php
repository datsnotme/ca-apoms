<?php

namespace App\Enums;

/**
 * Shared active/inactive status for Users, Departments, and Programs.
 * Stored as the string value, not a MySQL ENUM column, so new statuses
 * can be introduced without a schema change.
 */
enum ActiveStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }
}

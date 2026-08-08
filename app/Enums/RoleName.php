<?php

namespace App\Enums;

/**
 * Centralized role slugs, matching the spatie/laravel-permission `roles.name`
 * seeded in RolesAndPermissionsSeeder. Use these constants instead of raw
 * strings anywhere a role is checked or assigned.
 */
enum RoleName: string
{
    case Administrator = 'college-administrator';
    case Dean = 'college-dean';
    case DepartmentHead = 'department-head';
    case Faculty = 'faculty-member';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'College Administrator',
            self::Dean => 'College Dean',
            self::DepartmentHead => 'Department Head',
            self::Faculty => 'Faculty Member',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }
}

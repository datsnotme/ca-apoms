<?php

namespace App\Enums;

enum FacultyAssignmentRole: string
{
    case Primary = 'primary';
    case CoFaculty = 'co-faculty';

    public function label(): string
    {
        return match ($this) {
            self::Primary => 'Primary',
            self::CoFaculty => 'Co-Faculty',
        };
    }
}

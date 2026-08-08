<?php

namespace App\Enums;

enum AlertType: string
{
    case MultipleDeficiencies = 'multiple_deficiencies';
    case LowGwa = 'low_gwa';
    case EnrollmentStatus = 'enrollment_status';

    public function label(): string
    {
        return match ($this) {
            self::MultipleDeficiencies => 'Multiple Deficiencies',
            self::LowGwa => 'Low GWA',
            self::EnrollmentStatus => 'Enrollment Status',
        };
    }
}

<?php

namespace App\Enums;

enum EnrollmentCourseStatus: string
{
    case Enrolled = 'Enrolled';
    case Added = 'Added';
    case Dropped = 'Dropped';
    case Withdrawn = 'Withdrawn';
    case Completed = 'Completed';
    case Failed = 'Failed';
    case Incomplete = 'Incomplete';
    case Credited = 'Credited';
    case Repeated = 'Repeated';

    public function label(): string
    {
        return $this->value;
    }

    /**
     * Statuses that count as "still occupying a seat" in the class section
     * for capacity checks and duplicate-enrollment prevention.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::Enrolled, self::Added, self::Repeated], true);
    }
}

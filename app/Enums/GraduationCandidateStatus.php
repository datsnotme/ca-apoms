<?php

namespace App\Enums;

enum GraduationCandidateStatus: string
{
    case Nominated = 'nominated';
    case UnderEvaluation = 'under_evaluation';
    case Recommended = 'recommended';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Graduated = 'graduated';

    public function label(): string
    {
        return match ($this) {
            self::Nominated => 'Nominated',
            self::UnderEvaluation => 'Under Evaluation',
            self::Recommended => 'Recommended',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Graduated => 'Graduated',
        };
    }
}

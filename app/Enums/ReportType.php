<?php

namespace App\Enums;

enum ReportType: string
{
    case Enrollment = 'enrollment';
    case Grades = 'grades';
    case AtRisk = 'at-risk';
    case FacultyWorkload = 'faculty-workload';
    case GraduationPipeline = 'graduation-pipeline';

    public function label(): string
    {
        return match ($this) {
            self::Enrollment => 'Enrollment Summary',
            self::Grades => 'Academic Performance (Grade Distribution)',
            self::AtRisk => 'At-Risk & Progress Summary',
            self::FacultyWorkload => 'Faculty Workload Summary',
            self::GraduationPipeline => 'Graduation Pipeline Summary',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Enrollment => 'Active enrollment counts by program for a selected semester.',
            self::Grades => 'Distribution of finalized grades for a selected semester.',
            self::AtRisk => 'Every student currently carrying an unresolved progress alert.',
            self::FacultyWorkload => 'Sections and total teaching units per faculty member for a selected semester.',
            self::GraduationPipeline => 'Every graduation candidate and their current pipeline status for a selected term.',
        };
    }

    /**
     * Which filter inputs the frontend should render for this report.
     *
     * @return array<int, string>
     */
    public function availableFilters(): array
    {
        return match ($this) {
            self::Enrollment, self::Grades, self::FacultyWorkload, self::GraduationPipeline => ['semester_id'],
            self::AtRisk => [],
        };
    }
}

<?php

namespace App\Enums;

/**
 * Which section of the printed Individual Student Evaluation form a course
 * belongs to. Independent of CourseCategory (subject-domain classification,
 * e.g. Crop Science vs Animal Science) — a course has exactly one of each.
 */
enum CourseBucket: string
{
    case GeneralEducation = 'general_education';
    case MajorSubjects = 'major_subjects';
    case RequiredCourses = 'required_courses';
    case PhysicalEducation = 'physical_education';
    case NonAcademicRequirement = 'non_academic_requirement';

    public function label(): string
    {
        return match ($this) {
            self::GeneralEducation => 'General Education',
            self::MajorSubjects => 'Major Subjects',
            self::RequiredCourses => 'Required Courses',
            self::PhysicalEducation => 'Physical Education',
            self::NonAcademicRequirement => 'Non-Academic Requirement',
        };
    }
}

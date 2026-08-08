<?php

namespace App\Enums;

enum ImportType: string
{
    case Students = 'STUDENTS';
    case Courses = 'COURSES';
    case CurriculumCourses = 'CURRICULUM_COURSES';
    case Enrollment = 'ENROLLMENT';
    case Grades = 'GRADES';

    public function label(): string
    {
        return match ($this) {
            self::Students => 'Students',
            self::Courses => 'Courses',
            self::CurriculumCourses => 'Curriculum Courses',
            self::Enrollment => 'Enrollment',
            self::Grades => 'Grades',
        };
    }
}

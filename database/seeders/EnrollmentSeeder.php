<?php

namespace Database\Seeders;

use App\Models\ClassSection;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\Department;
use App\Models\Facility;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentGrade;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * A handful of open class sections (with a primary faculty and a schedule)
 * per department for the current semester, plus enrollments for a few of
 * the seeded students, so Class Sections / Enrollment have demo data. Since
 * there is no dedicated CourseSeeder yet, this also seeds two sample
 * courses per department if none exist — kept minimal on purpose. Also adds
 * both sample courses to the department's seeded curriculum (year 1) and
 * finalizes one student's grade in the first course, so the Phase 3
 * Progress page has a completed course and a GWA to show, not just an
 * empty checklist.
 */
class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $semester = Semester::where('is_current', true)->first();

        if (! $semester) {
            return;
        }

        foreach (Department::all() as $department) {
            $courses = Course::where('department_id', $department->id)->limit(2)->get();

            if ($courses->count() < 2) {
                $courses = collect([
                    Course::firstOrCreate(
                        ['code' => "{$department->code}-101"],
                        ['department_id' => $department->id, 'title' => 'Foundations of the Discipline', 'units' => 3, 'category' => 'general_education', 'is_active' => true]
                    ),
                    Course::firstOrCreate(
                        ['code' => "{$department->code}-201"],
                        ['department_id' => $department->id, 'title' => 'Intermediate Practicum', 'units' => 3, 'category' => 'practicum', 'is_active' => true]
                    ),
                ]);
            }

            $faculty = User::role('faculty-member')->where('department_id', $department->id)->first();

            $curriculum = Curriculum::whereHas('program', fn ($q) => $q->where('department_id', $department->id))->first();

            foreach ($courses as $index => $course) {
                $curriculum?->curriculumCourses()->firstOrCreate(
                    ['course_id' => $course->id],
                    ['year_level' => 1, 'semester' => 'FIRST', 'is_required' => true, 'units' => $course->units, 'sequence_order' => $index + 1]
                );
            }

            foreach ($courses as $course) {
                $section = ClassSection::firstOrCreate(
                    ['course_id' => $course->id, 'semester_id' => $semester->id, 'section_label' => 'A'],
                    ['max_students' => 30, 'status' => 'open']
                );

                if ($faculty) {
                    $section->facultyAssignments()->firstOrCreate(['role' => 'primary'], ['faculty_id' => $faculty->id]);
                }

                $facility = Facility::firstOrCreate(
                    ['name' => 'Room 101'],
                    ['type' => 'Classroom', 'is_active' => true]
                );

                $section->schedules()->firstOrCreate([
                    'day_of_week' => 'monday',
                    'start_time' => '08:00',
                    'end_time' => '09:30',
                ], ['facility_id' => $facility->id]);
            }

            $students = Student::where('department_id', $department->id)
                ->where('status', 'active')
                ->limit(3)
                ->get();

            foreach ($students as $studentIndex => $student) {
                $enrollment = StudentEnrollment::firstOrCreate(
                    ['student_id' => $student->id, 'semester_id' => $semester->id],
                    ['status' => 'enrolled', 'enrolled_by' => null]
                );

                foreach ($courses as $courseIndex => $course) {
                    $section = ClassSection::where('course_id', $course->id)->where('semester_id', $semester->id)->first();

                    if (! $section) {
                        continue;
                    }

                    $enrollmentCourse = $enrollment->enrollmentCourses()->firstOrCreate(
                        ['class_section_id' => $section->id],
                        ['status' => 'Enrolled']
                    );

                    if ($studentIndex === 0 && $courseIndex === 0 && $faculty) {
                        StudentGrade::firstOrCreate(
                            ['enrollment_course_id' => $enrollmentCourse->id],
                            ['grade' => '1.50', 'status' => 'finalized', 'encoded_by' => $faculty->id]
                        );
                    }
                }
            }
        }
    }
}

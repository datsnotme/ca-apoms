<?php

use App\Enums\RoleName;
use App\Models\AcademicDeficiency;
use App\Models\ClassSection;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use App\Models\Department;
use App\Models\EnrollmentCourse;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentGrade;
use App\Models\YearLevel;
use App\Services\ProgressComputationService;
use Database\Seeders\GradingScaleSeeder;

beforeEach(function () {
    $this->seed(GradingScaleSeeder::class);

    $this->department = Department::factory()->create();
    $this->curriculum = Curriculum::factory()->create();
    $this->semester = Semester::factory()->create();
    $this->yearLevel1 = YearLevel::factory()->create(['level' => 1]);
    $this->yearLevel2 = YearLevel::factory()->create(['level' => 2]);

    $this->student = Student::factory()->create([
        'department_id' => $this->department->id,
        'curriculum_id' => $this->curriculum->id,
        'year_level_id' => $this->yearLevel2->id,
    ]);

    $this->service = app(ProgressComputationService::class);
});

function enrollStudentInCourse(Student $student, Semester $semester, Course $course, string $sectionLabel = 'A'): EnrollmentCourse
{
    $section = ClassSection::factory()->create(['course_id' => $course->id, 'semester_id' => $semester->id, 'section_label' => $sectionLabel]);
    $enrollment = StudentEnrollment::firstOrCreate(['student_id' => $student->id, 'semester_id' => $semester->id], ['status' => 'enrolled']);

    return EnrollmentCourse::factory()->create(['student_enrollment_id' => $enrollment->id, 'class_section_id' => $section->id, 'status' => 'Enrolled']);
}

test('a finalized passing grade shows as completed and counts toward GWA', function () {
    $course = Course::factory()->create(['department_id' => $this->department->id]);
    $cc = CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $course->id, 'year_level' => 1, 'units' => 3]);

    $ec = enrollStudentInCourse($this->student, $this->semester, $course);
    StudentGrade::factory()->create(['enrollment_course_id' => $ec->id, 'grade' => '1.00', 'status' => 'finalized']);

    $row = $this->service->checklist($this->student)->firstWhere('curriculum_course_id', $cc->id);

    expect($row['status'])->toBe('completed');
    expect($row['is_deficiency'])->toBeFalse();
    expect($this->service->gwa($this->student))->toBe(1.0);
});

test('a finalized failing grade past its year level is a deficiency', function () {
    $course = Course::factory()->create(['department_id' => $this->department->id]);
    $cc = CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $course->id, 'year_level' => 1, 'units' => 3]);

    $ec = enrollStudentInCourse($this->student, $this->semester, $course);
    StudentGrade::factory()->create(['enrollment_course_id' => $ec->id, 'grade' => '5.00', 'status' => 'finalized']);

    $row = $this->service->checklist($this->student)->firstWhere('curriculum_course_id', $cc->id);

    expect($row['status'])->toBe('failed');
    expect($row['is_deficiency'])->toBeTrue();

    $this->service->syncDeficiencies($this->student);

    $this->assertDatabaseHas('academic_deficiencies', [
        'student_id' => $this->student->id,
        'curriculum_course_id' => $cc->id,
        'deficiency_type' => 'failed',
        'resolved_at' => null,
    ]);
});

test('a required course not yet reached is pending, not a deficiency', function () {
    $course = Course::factory()->create(['department_id' => $this->department->id]);
    $cc = CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $course->id, 'year_level' => 3, 'units' => 3]);

    $row = $this->service->checklist($this->student)->firstWhere('curriculum_course_id', $cc->id);

    expect($row['status'])->toBe('pending');
    expect($row['is_deficiency'])->toBeFalse();
});

test('a retake that later passes shows completed, not failed', function () {
    $course = Course::factory()->create(['department_id' => $this->department->id]);
    $cc = CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $course->id, 'year_level' => 1, 'units' => 3]);

    $firstAttempt = enrollStudentInCourse($this->student, $this->semester, $course, 'A');
    StudentGrade::factory()->create(['enrollment_course_id' => $firstAttempt->id, 'grade' => '5.00', 'status' => 'finalized']);

    $laterSemester = Semester::factory()->create();
    $retake = enrollStudentInCourse($this->student, $laterSemester, $course, 'A');
    StudentGrade::factory()->create(['enrollment_course_id' => $retake->id, 'grade' => '1.50', 'status' => 'finalized']);

    $row = $this->service->checklist($this->student)->firstWhere('curriculum_course_id', $cc->id);

    expect($row['status'])->toBe('completed');
    expect($row['is_deficiency'])->toBeFalse();
});

test('a retake for grade improvement shows the latest passing grade, not the first one that happened to pass', function () {
    $course = Course::factory()->create(['department_id' => $this->department->id]);
    $cc = CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $course->id, 'year_level' => 1, 'units' => 3]);

    $firstAttempt = enrollStudentInCourse($this->student, $this->semester, $course, 'A');
    StudentGrade::factory()->create(['enrollment_course_id' => $firstAttempt->id, 'grade' => '2.00', 'status' => 'finalized']);

    $laterSemester = Semester::factory()->create();
    $retake = enrollStudentInCourse($this->student, $laterSemester, $course, 'A');
    StudentGrade::factory()->create(['enrollment_course_id' => $retake->id, 'grade' => '1.00', 'status' => 'finalized']);

    $row = $this->service->checklist($this->student)->firstWhere('curriculum_course_id', $cc->id);

    expect($row['status'])->toBe('completed');
    expect($row['grade'])->toBe('1.00');
    expect($row['numeric_equivalent'])->toBe(1.0);
    expect($this->service->gwa($this->student))->toBe(1.0);
});

test('syncDeficiencies auto-resolves a deficiency once a later retake passes', function () {
    $course = Course::factory()->create(['department_id' => $this->department->id]);
    $cc = CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $course->id, 'year_level' => 1, 'units' => 3]);

    $ec = enrollStudentInCourse($this->student, $this->semester, $course);
    StudentGrade::factory()->create(['enrollment_course_id' => $ec->id, 'grade' => '5.00', 'status' => 'finalized']);

    $this->service->syncDeficiencies($this->student);
    $this->assertDatabaseHas('academic_deficiencies', ['student_id' => $this->student->id, 'curriculum_course_id' => $cc->id, 'resolved_at' => null]);

    $laterSemester = Semester::factory()->create();
    $retake = enrollStudentInCourse($this->student, $laterSemester, $course, 'A');
    StudentGrade::factory()->create(['enrollment_course_id' => $retake->id, 'grade' => '1.00', 'status' => 'finalized']);

    $this->service->syncDeficiencies($this->student);

    $deficiency = AcademicDeficiency::where('student_id', $this->student->id)->where('curriculum_course_id', $cc->id)->firstOrFail();
    expect($deficiency->resolved_at)->not->toBeNull();
});

test('a non-finalized grade counts as in progress, not completed or failed', function () {
    $course = Course::factory()->create(['department_id' => $this->department->id]);
    $cc = CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $course->id, 'year_level' => 1, 'units' => 3]);

    $ec = enrollStudentInCourse($this->student, $this->semester, $course);
    StudentGrade::factory()->create(['enrollment_course_id' => $ec->id, 'grade' => '1.00', 'status' => 'submitted']);

    $row = $this->service->checklist($this->student)->firstWhere('curriculum_course_id', $cc->id);

    expect($row['status'])->toBe('in_progress');
    expect($row['is_deficiency'])->toBeFalse();
});

test('the assigned adviser can view progress but a non-adviser faculty cannot', function () {
    $adviser = userWithRole(RoleName::Faculty->value, $this->department);
    $otherFaculty = userWithRole(RoleName::Faculty->value, $this->department);
    $this->student->update(['adviser_id' => $adviser->id]);

    $this->actingAs($adviser)->get("/students/{$this->student->id}/progress")->assertOk();
    $this->actingAs($otherFaculty)->get("/students/{$this->student->id}/progress")->assertForbidden();
});

test('a department head can view progress for their own department only', function () {
    $head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $otherDepartment = Department::factory()->create();
    $otherHead = userWithRole(RoleName::DepartmentHead->value, $otherDepartment);

    $this->actingAs($head)->get("/students/{$this->student->id}/progress")->assertOk();
    $this->actingAs($otherHead)->get("/students/{$this->student->id}/progress")->assertForbidden();
});

test('admin and dean can view progress for any student', function () {
    $admin = userWithRole(RoleName::Administrator->value);
    $dean = userWithRole(RoleName::Dean->value);

    $this->actingAs($admin)->get("/students/{$this->student->id}/progress")->assertOk();
    $this->actingAs($dean)->get("/students/{$this->student->id}/progress")->assertOk();
});

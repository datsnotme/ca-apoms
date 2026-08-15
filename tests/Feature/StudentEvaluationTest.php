<?php

use App\Enums\RoleName;
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
use App\Services\StudentEvaluationService;
use Database\Seeders\GradingScaleSeeder;

beforeEach(function () {
    $this->seed(GradingScaleSeeder::class);

    $this->department = Department::factory()->create();
    $this->curriculum = Curriculum::factory()->create();
    $this->semester = Semester::factory()->create();
    $this->yearLevel2 = YearLevel::factory()->create(['level' => 2]);

    $this->student = Student::factory()->create([
        'department_id' => $this->department->id,
        'curriculum_id' => $this->curriculum->id,
        'year_level_id' => $this->yearLevel2->id,
    ]);

    $this->service = app(StudentEvaluationService::class);
});

function enrollStudentInEvalCourse(Student $student, Semester $semester, Course $course): EnrollmentCourse
{
    $section = ClassSection::factory()->create(['course_id' => $course->id, 'semester_id' => $semester->id]);
    $enrollment = StudentEnrollment::firstOrCreate(['student_id' => $student->id, 'semester_id' => $semester->id], ['status' => 'enrolled']);

    return EnrollmentCourse::factory()->create(['student_enrollment_id' => $enrollment->id, 'class_section_id' => $section->id, 'status' => 'Enrolled']);
}

test('courses are grouped by year level and then by semester within each year', function () {
    $year1Course = Course::factory()->create(['department_id' => $this->department->id, 'bucket' => 'major_subjects']);
    $year2CourseFirst = Course::factory()->create(['department_id' => $this->department->id, 'bucket' => 'major_subjects']);
    $year2CourseSecond = Course::factory()->create(['department_id' => $this->department->id, 'bucket' => 'major_subjects']);

    CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $year1Course->id, 'year_level' => 1, 'semester' => 'FIRST', 'units' => 3]);
    CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $year2CourseFirst->id, 'year_level' => 2, 'semester' => 'FIRST', 'units' => 3]);
    CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $year2CourseSecond->id, 'year_level' => 2, 'semester' => 'SECOND', 'units' => 3]);

    $result = $this->service->evaluate($this->student);

    expect($result['years'])->toHaveCount(2);
    expect($result['years'][0]['year_level'])->toBe(1);
    expect($result['years'][0]['semesters'])->toHaveCount(1);
    expect($result['years'][0]['semesters'][0]['semester'])->toBe('FIRST');
    expect($result['years'][0]['semesters'][0]['rows'])->toHaveCount(1);

    expect($result['years'][1]['year_level'])->toBe(2);
    expect($result['years'][1]['semesters'])->toHaveCount(2);
    expect($result['years'][1]['semesters'][0]['semester'])->toBe('FIRST');
    expect($result['years'][1]['semesters'][1]['semester'])->toBe('SECOND');
});

test('years beyond the student\'s current year level are excluded', function () {
    // $this->student is at year_level 2 (see beforeEach).
    $futureCourse = Course::factory()->create(['department_id' => $this->department->id, 'bucket' => 'major_subjects']);
    CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $futureCourse->id, 'year_level' => 4, 'units' => 3]);

    $result = $this->service->evaluate($this->student);

    expect($result['years']->pluck('year_level')->all())->not->toContain(4);
});

test('bucket_summary always lists all five buckets, even when empty, and totals their units', function () {
    $course = Course::factory()->create(['department_id' => $this->department->id, 'bucket' => 'major_subjects']);
    CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $course->id, 'year_level' => 1, 'units' => 3]);

    $result = $this->service->evaluate($this->student);

    expect($result['bucket_summary']->pluck('label')->all())->toBe([
        'General Education', 'Major Subjects', 'Required Courses', 'Physical Education', 'Non-Academic Requirement',
    ]);

    $major = $result['bucket_summary']->firstWhere('label', 'Major Subjects');
    expect($major['total_units'])->toBe(3.0);

    $pe = $result['bucket_summary']->firstWhere('label', 'Physical Education');
    expect($pe['total_units'])->toBe(0.0);
});

test('the summary accounts for required, earned, in-progress, and incomplete units', function () {
    $completed = Course::factory()->create(['department_id' => $this->department->id, 'bucket' => 'major_subjects']);
    $inProgress = Course::factory()->create(['department_id' => $this->department->id, 'bucket' => 'major_subjects']);
    $incomplete = Course::factory()->create(['department_id' => $this->department->id, 'bucket' => 'major_subjects']);
    $notTaken = Course::factory()->create(['department_id' => $this->department->id, 'bucket' => 'major_subjects']);

    CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $completed->id, 'year_level' => 1, 'units' => 3]);
    CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $inProgress->id, 'year_level' => 1, 'units' => 3]);
    CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $incomplete->id, 'year_level' => 1, 'units' => 3]);
    CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $notTaken->id, 'year_level' => 1, 'units' => 3]);

    $ec1 = enrollStudentInEvalCourse($this->student, $this->semester, $completed);
    StudentGrade::factory()->create(['enrollment_course_id' => $ec1->id, 'grade' => '1.00', 'status' => 'finalized']);

    $ec2 = enrollStudentInEvalCourse($this->student, $this->semester, $inProgress);
    StudentGrade::factory()->create(['enrollment_course_id' => $ec2->id, 'grade' => '1.00', 'status' => 'submitted']);

    $ec3 = enrollStudentInEvalCourse($this->student, $this->semester, $incomplete);
    StudentGrade::factory()->create(['enrollment_course_id' => $ec3->id, 'grade' => 'INC', 'status' => 'finalized']);

    $summary = $this->service->evaluate($this->student)['summary'];

    expect($summary['total_units_required'])->toBe(12.0);
    expect($summary['total_units_earned'])->toBe(3.0);
    expect($summary['currently_enrolled_units'])->toBe(3.0);
    expect($summary['taken_no_grade_units'])->toBe(0.0);
    expect($summary['incomplete_units'])->toBe(3.0);
    expect($summary['remaining_units'])->toBe(3.0);
});

test('a student with no failed, incomplete, or dropped courses is suggested regular', function () {
    $course = Course::factory()->create(['department_id' => $this->department->id, 'bucket' => 'major_subjects']);
    CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $course->id, 'year_level' => 1, 'units' => 3]);

    $ec = enrollStudentInEvalCourse($this->student, $this->semester, $course);
    StudentGrade::factory()->create(['enrollment_course_id' => $ec->id, 'grade' => '1.00', 'status' => 'finalized']);

    $result = $this->service->evaluate($this->student);

    expect($result['suggested_classification'])->toBe('regular');
    expect($result['flagged_courses'])->toHaveCount(0);
});

test('a student with an incomplete grade is suggested irregular and flagged', function () {
    $course = Course::factory()->create(['department_id' => $this->department->id, 'bucket' => 'major_subjects']);
    CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $course->id, 'year_level' => 1, 'units' => 3]);

    $ec = enrollStudentInEvalCourse($this->student, $this->semester, $course);
    StudentGrade::factory()->create(['enrollment_course_id' => $ec->id, 'grade' => 'INC', 'status' => 'finalized']);

    $result = $this->service->evaluate($this->student);

    expect($result['suggested_classification'])->toBe('irregular');
    expect($result['flagged_courses'])->toHaveCount(1);
    expect($result['flagged_courses'][0]['status'])->toBe('incomplete');
});

test('generating the evaluation writes nothing to the database', function () {
    $course = Course::factory()->create(['department_id' => $this->department->id, 'bucket' => 'major_subjects']);
    CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $course->id, 'year_level' => 1, 'units' => 3]);

    $ec = enrollStudentInEvalCourse($this->student, $this->semester, $course);
    StudentGrade::factory()->create(['enrollment_course_id' => $ec->id, 'grade' => '5.00', 'status' => 'finalized']);

    $classification = $this->student->classification;

    $this->service->evaluate($this->student);

    expect($this->student->fresh()->classification)->toEqual($classification);
    $this->assertDatabaseCount('academic_deficiencies', 0);
});

test('the assigned adviser can download the evaluation pdf but a non-adviser faculty cannot', function () {
    $adviser = userWithRole(RoleName::Faculty->value, $this->department);
    $otherFaculty = userWithRole(RoleName::Faculty->value, $this->department);
    $this->student->update(['adviser_id' => $adviser->id]);

    $response = $this->actingAs($adviser)->get("/students/{$this->student->id}/evaluation");
    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');

    $this->actingAs($otherFaculty)->get("/students/{$this->student->id}/evaluation")->assertForbidden();
});

test('admin can download the evaluation pdf for any student', function () {
    $admin = userWithRole(RoleName::Administrator->value);

    $this->actingAs($admin)->get("/students/{$this->student->id}/evaluation")->assertOk();
});

test('the evaluate-student index lists only students within the users scope', function () {
    $adviser = userWithRole(RoleName::Faculty->value, $this->department);
    $otherFaculty = userWithRole(RoleName::Faculty->value, $this->department);
    $this->student->update(['adviser_id' => $adviser->id]);

    $otherStudent = Student::factory()->create([
        'department_id' => $this->department->id,
        'adviser_id' => $otherFaculty->id,
    ]);

    $response = $this->actingAs($adviser)->get('/evaluate-student');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Progress/EvaluationIndex')
        ->has('students.data', 1)
        ->where('students.data.0.id', $this->student->id));
});

test('a department head sees every student in their department on the evaluate-student index', function () {
    $head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $otherDepartment = Department::factory()->create();
    Student::factory()->create(['department_id' => $otherDepartment->id]);

    $response = $this->actingAs($head)->get('/evaluate-student');

    $response->assertInertia(fn ($page) => $page->has('students.data', 1));
});

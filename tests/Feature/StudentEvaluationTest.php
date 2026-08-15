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

test('checklist courses are grouped into their form bucket', function () {
    $gecCourse = Course::factory()->create(['department_id' => $this->department->id, 'bucket' => 'general_education']);
    $majorCourse = Course::factory()->create(['department_id' => $this->department->id, 'bucket' => 'major_subjects']);

    CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $gecCourse->id, 'year_level' => 1, 'units' => 3]);
    CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $majorCourse->id, 'year_level' => 1, 'units' => 3]);

    $result = $this->service->evaluate($this->student);

    $labels = $result['buckets']->pluck('label')->all();
    expect($labels)->toContain('General Education', 'Major Subjects');

    $gec = $result['buckets']->firstWhere('bucket', 'general_education');
    expect($gec['rows'])->toHaveCount(1);
    expect($gec['rows'][0]['course']['code'])->toBe($gecCourse->code);
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

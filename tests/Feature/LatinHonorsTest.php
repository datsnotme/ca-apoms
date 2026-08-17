<?php

use App\Enums\LatinHonorsTier;
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
use App\Models\User;
use App\Models\YearLevel;
use App\Services\LatinHonorsService;
use Database\Seeders\GradingScaleSeeder;

beforeEach(function () {
    $this->seed(GradingScaleSeeder::class);

    $this->department = Department::factory()->create();
    $this->curriculum = Curriculum::factory()->create();
    $this->semester = Semester::factory()->create();
    $this->yearLevel = YearLevel::factory()->create(['level' => 4]);

    $this->service = app(LatinHonorsService::class);
});

function enrollAndGradeForLatinHonors(Student $student, Semester $semester, Course $course, string $grade): void
{
    $section = ClassSection::factory()->create(['course_id' => $course->id, 'semester_id' => $semester->id]);
    $enrollment = StudentEnrollment::firstOrCreate(['student_id' => $student->id, 'semester_id' => $semester->id], ['status' => 'enrolled']);
    $ec = EnrollmentCourse::factory()->create(['student_enrollment_id' => $enrollment->id, 'class_section_id' => $section->id, 'status' => 'Enrolled']);
    StudentGrade::factory()->create(['enrollment_course_id' => $ec->id, 'grade' => $grade, 'status' => 'finalized']);
}

function makeQualifyingStudent(Department $department, Curriculum $curriculum, YearLevel $yearLevel, Semester $semester, string $grade = '1.50'): Student
{
    $student = Student::factory()->create([
        'department_id' => $department->id,
        'curriculum_id' => $curriculum->id,
        'year_level_id' => $yearLevel->id,
        'status' => 'active',
    ]);

    $course = Course::factory()->create(['department_id' => $department->id]);
    CurriculumCourse::factory()->create(['curriculum_id' => $curriculum->id, 'course_id' => $course->id, 'year_level' => 1, 'units' => 3]);
    enrollAndGradeForLatinHonors($student, $semester, $course, $grade);

    return $student;
}

test('a student with a qualifying GWA, full completion, and no deficiencies is a prospect', function () {
    $admin = userWithRole(RoleName::Administrator->value);
    $student = makeQualifyingStudent($this->department, $this->curriculum, $this->yearLevel, $this->semester, '1.25');

    $prospects = $this->service->identifyProspects($admin);

    expect($prospects->pluck('student.id'))->toContain($student->id);
    $row = $prospects->firstWhere('student.id', $student->id);
    expect($row['gwa'])->toBe(1.25);
    expect($row['tier'])->toBe(LatinHonorsTier::MagnaCumLaude);
});

test('LatinHonorsTier resolves the standard boundaries correctly', function () {
    expect(LatinHonorsTier::forGwa(1.00))->toBe(LatinHonorsTier::SummaCumLaude);
    expect(LatinHonorsTier::forGwa(1.20))->toBe(LatinHonorsTier::SummaCumLaude);
    expect(LatinHonorsTier::forGwa(1.21))->toBe(LatinHonorsTier::MagnaCumLaude);
    expect(LatinHonorsTier::forGwa(1.45))->toBe(LatinHonorsTier::MagnaCumLaude);
    expect(LatinHonorsTier::forGwa(1.46))->toBe(LatinHonorsTier::CumLaude);
    expect(LatinHonorsTier::forGwa(1.75))->toBe(LatinHonorsTier::CumLaude);
    expect(LatinHonorsTier::forGwa(1.76))->toBeNull();
});

test('a student whose GWA is above the cutoff is excluded', function () {
    $admin = userWithRole(RoleName::Administrator->value);
    $student = makeQualifyingStudent($this->department, $this->curriculum, $this->yearLevel, $this->semester, '2.00');

    $prospects = $this->service->identifyProspects($admin);

    expect($prospects->pluck('student.id'))->not->toContain($student->id);
});

test('a student with an unresolved deficiency is excluded even with a qualifying gwa', function () {
    $admin = userWithRole(RoleName::Administrator->value);
    $student = makeQualifyingStudent($this->department, $this->curriculum, $this->yearLevel, $this->semester, '1.25');

    AcademicDeficiency::factory()->create([
        'student_id' => $student->id,
        'curriculum_course_id' => CurriculumCourse::first()->id,
        'resolved_at' => null,
    ]);

    $prospects = $this->service->identifyProspects($admin);

    expect($prospects->pluck('student.id'))->not->toContain($student->id);
});

test('a student with incomplete curriculum coverage is excluded', function () {
    $admin = userWithRole(RoleName::Administrator->value);
    $student = makeQualifyingStudent($this->department, $this->curriculum, $this->yearLevel, $this->semester, '1.25');

    // A second required course the student has not taken drops completion below 100%.
    $untakenCourse = Course::factory()->create(['department_id' => $this->department->id]);
    CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $untakenCourse->id, 'year_level' => 1, 'units' => 3]);

    $prospects = $this->service->identifyProspects($admin);

    expect($prospects->pluck('student.id'))->not->toContain($student->id);
});

test('a department head only sees prospects from their own department', function () {
    $head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $otherDepartment = Department::factory()->create();
    $otherCurriculum = Curriculum::factory()->create();

    $ownStudent = makeQualifyingStudent($this->department, $this->curriculum, $this->yearLevel, $this->semester, '1.25');
    $otherStudent = makeQualifyingStudent($otherDepartment, $otherCurriculum, $this->yearLevel, $this->semester, '1.25');

    $prospects = $this->service->identifyProspects($head);

    expect($prospects->pluck('student.id'))->toContain($ownStudent->id);
    expect($prospects->pluck('student.id'))->not->toContain($otherStudent->id);
});

test('the latin-honors page requires the graduation.view permission', function () {
    $admin = userWithRole(RoleName::Administrator->value);
    $userWithNoRole = User::factory()->create();

    $this->actingAs($admin)->get('/latin-honors')->assertOk();
    $this->actingAs($userWithNoRole)->get('/latin-honors')->assertForbidden();
});

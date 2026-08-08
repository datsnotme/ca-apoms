<?php

use App\Enums\RoleName;
use App\Models\ClassSection;
use App\Models\Course;
use App\Models\Department;
use App\Models\EnrollmentCourse;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;

beforeEach(function () {
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->department = Department::factory()->create();
    $this->course = Course::factory()->create(['department_id' => $this->department->id]);
    $this->semester = Semester::factory()->create();
    $this->student = Student::factory()->create(['department_id' => $this->department->id]);
    $this->section = ClassSection::factory()->create([
        'course_id' => $this->course->id,
        'semester_id' => $this->semester->id,
        'max_students' => 2,
    ]);
});

test('an admin can create a student enrollment for a semester', function () {
    $response = $this->actingAs($this->admin)->post('/enrollments', [
        'student_id' => $this->student->id,
        'semester_id' => $this->semester->id,
    ]);

    $enrollment = StudentEnrollment::where('student_id', $this->student->id)->firstOrFail();
    $response->assertRedirect("/enrollments/{$enrollment->id}/edit");
    $this->assertDatabaseHas('student_enrollments', [
        'student_id' => $this->student->id,
        'semester_id' => $this->semester->id,
        'enrolled_by' => $this->admin->id,
    ]);
});

test('a student cannot have two enrollment records in the same semester', function () {
    StudentEnrollment::factory()->create(['student_id' => $this->student->id, 'semester_id' => $this->semester->id]);

    $response = $this->actingAs($this->admin)->post('/enrollments', [
        'student_id' => $this->student->id,
        'semester_id' => $this->semester->id,
    ]);

    $response->assertSessionHasErrors('student_id');
});

test('a course can be added to an enrollment', function () {
    $enrollment = StudentEnrollment::factory()->create(['student_id' => $this->student->id, 'semester_id' => $this->semester->id]);

    $response = $this->actingAs($this->admin)->post("/enrollments/{$enrollment->id}/courses", [
        'class_section_id' => $this->section->id,
    ]);

    $response->assertRedirect("/enrollments/{$enrollment->id}/edit");
    $this->assertDatabaseHas('enrollment_courses', [
        'student_enrollment_id' => $enrollment->id,
        'class_section_id' => $this->section->id,
        'status' => 'Enrolled',
    ]);
});

test('adding the same course twice in one semester is rejected unless marked as a repeat', function () {
    $enrollment = StudentEnrollment::factory()->create(['student_id' => $this->student->id, 'semester_id' => $this->semester->id]);
    EnrollmentCourse::factory()->create([
        'student_enrollment_id' => $enrollment->id,
        'class_section_id' => $this->section->id,
        'status' => 'Enrolled',
    ]);

    $otherSection = ClassSection::factory()->create([
        'course_id' => $this->course->id,
        'semester_id' => $this->semester->id,
        'section_label' => 'Z',
        'max_students' => 5,
    ]);

    $rejected = $this->actingAs($this->admin)->post("/enrollments/{$enrollment->id}/courses", [
        'class_section_id' => $otherSection->id,
    ]);
    $rejected->assertSessionHasErrors('class_section_id');

    $allowed = $this->actingAs($this->admin)->post("/enrollments/{$enrollment->id}/courses", [
        'class_section_id' => $otherSection->id,
        'allow_repeat' => true,
    ]);
    $allowed->assertRedirect();
    $this->assertDatabaseHas('enrollment_courses', [
        'student_enrollment_id' => $enrollment->id,
        'class_section_id' => $otherSection->id,
        'status' => 'Repeated',
    ]);
});

test('a class section cannot be filled past its max_students capacity', function () {
    $enrollment = StudentEnrollment::factory()->create(['student_id' => $this->student->id, 'semester_id' => $this->semester->id]);
    $otherStudents = Student::factory()->count(2)->create(['department_id' => $this->department->id]);

    foreach ($otherStudents as $s) {
        $otherEnrollment = StudentEnrollment::factory()->create(['student_id' => $s->id, 'semester_id' => $this->semester->id]);
        EnrollmentCourse::factory()->create([
            'student_enrollment_id' => $otherEnrollment->id,
            'class_section_id' => $this->section->id,
            'status' => 'Enrolled',
        ]);
    }

    $response = $this->actingAs($this->admin)->post("/enrollments/{$enrollment->id}/courses", [
        'class_section_id' => $this->section->id,
    ]);

    $response->assertSessionHasErrors('class_section_id');
});

test('an admin can bulk archive multiple enrollments at once', function () {
    $students = Student::factory()->count(3)->create(['department_id' => $this->department->id]);
    $enrollments = $students->map(fn (Student $s) => StudentEnrollment::factory()->create([
        'student_id' => $s->id,
        'semester_id' => $this->semester->id,
    ]));

    $response = $this->actingAs($this->admin)->delete('/enrollments/bulk-destroy', [
        'ids' => $enrollments->pluck('id')->all(),
    ]);

    $response->assertRedirect('/enrollments')->assertSessionHas('success', '3 enrollment(s) archived.');
    $enrollments->each(fn (StudentEnrollment $e) => $this->assertSoftDeleted('student_enrollments', ['id' => $e->id]));
});

test('a department head bulk-selecting an enrollment from another department deletes nothing', function () {
    $otherDepartment = Department::factory()->create();
    $otherStudent = Student::factory()->create(['department_id' => $otherDepartment->id]);

    $myEnrollment = StudentEnrollment::factory()->create(['student_id' => $this->student->id, 'semester_id' => $this->semester->id]);
    $otherEnrollment = StudentEnrollment::factory()->create(['student_id' => $otherStudent->id, 'semester_id' => $this->semester->id]);

    $head = userWithRole(RoleName::DepartmentHead->value, $this->department);

    $this->actingAs($head)->delete('/enrollments/bulk-destroy', [
        'ids' => [$myEnrollment->id, $otherEnrollment->id],
    ])->assertForbidden();

    $this->assertDatabaseHas('student_enrollments', ['id' => $myEnrollment->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('student_enrollments', ['id' => $otherEnrollment->id, 'deleted_at' => null]);
});

test('a department head only sees enrollments from their own department', function () {
    $otherDepartment = Department::factory()->create();
    $otherStudent = Student::factory()->create(['department_id' => $otherDepartment->id]);

    StudentEnrollment::factory()->create(['student_id' => $this->student->id, 'semester_id' => $this->semester->id]);
    StudentEnrollment::factory()->create(['student_id' => $otherStudent->id, 'semester_id' => $this->semester->id]);

    $head = userWithRole(RoleName::DepartmentHead->value, $this->department);

    $response = $this->actingAs($head)->get('/enrollments');

    $response->assertInertia(fn ($page) => $page->has('enrollments.data', 1));
});

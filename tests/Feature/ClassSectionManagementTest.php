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
});

test('an admin can create a class section with a primary faculty assignment', function () {
    $faculty = userWithRole(RoleName::Faculty->value, $this->department);

    $response = $this->actingAs($this->admin)->post('/class-sections', [
        'course_id' => $this->course->id,
        'semester_id' => $this->semester->id,
        'section_label' => 'A',
        'max_students' => 30,
        'status' => 'open',
        'faculty_id' => $faculty->id,
    ]);

    $response->assertRedirect('/class-sections');
    $section = ClassSection::where('section_label', 'A')->firstOrFail();
    $this->assertDatabaseHas('faculty_assignments', [
        'class_section_id' => $section->id,
        'faculty_id' => $faculty->id,
        'role' => 'primary',
    ]);
});

test('the same course cannot have two sections with the same label in one semester', function () {
    ClassSection::factory()->create([
        'course_id' => $this->course->id,
        'semester_id' => $this->semester->id,
        'section_label' => 'A',
    ]);

    $response = $this->actingAs($this->admin)->post('/class-sections', [
        'course_id' => $this->course->id,
        'semester_id' => $this->semester->id,
        'section_label' => 'A',
        'max_students' => 30,
        'status' => 'open',
    ]);

    $response->assertSessionHasErrors('course_id');
});

test('a faculty member can view but not create class sections', function () {
    $faculty = userWithRole(RoleName::Faculty->value, $this->department);

    $this->actingAs($faculty)->get('/class-sections')->assertOk();
    $this->actingAs($faculty)->get('/class-sections/create')->assertForbidden();
});

test('a department head only sees class sections for their own department', function () {
    $otherDepartment = Department::factory()->create();
    $otherCourse = Course::factory()->create(['department_id' => $otherDepartment->id]);

    ClassSection::factory()->create(['course_id' => $this->course->id, 'semester_id' => $this->semester->id]);
    ClassSection::factory()->create(['course_id' => $otherCourse->id, 'semester_id' => $this->semester->id]);

    $head = userWithRole(RoleName::DepartmentHead->value, $this->department);

    $response = $this->actingAs($head)->get('/class-sections');

    $response->assertInertia(fn ($page) => $page->has('classSections.data', 1));
});

test('the roster page lists enrolled students for a class section', function () {
    $section = ClassSection::factory()->create(['course_id' => $this->course->id, 'semester_id' => $this->semester->id]);
    $student = Student::factory()->create(['department_id' => $this->department->id]);
    $enrollment = StudentEnrollment::factory()->create(['student_id' => $student->id, 'semester_id' => $this->semester->id]);
    EnrollmentCourse::factory()->create([
        'student_enrollment_id' => $enrollment->id,
        'class_section_id' => $section->id,
        'status' => 'Enrolled',
    ]);

    $response = $this->actingAs($this->admin)->get("/class-sections/{$section->id}/roster");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('roster', 1));
});

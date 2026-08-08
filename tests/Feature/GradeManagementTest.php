<?php

use App\Enums\RoleName;
use App\Models\ClassSection;
use App\Models\Course;
use App\Models\Department;
use App\Models\EnrollmentCourse;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentGrade;
use Database\Seeders\GradingScaleSeeder;

beforeEach(function () {
    $this->seed(GradingScaleSeeder::class);

    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->department = Department::factory()->create();
    $this->course = Course::factory()->create(['department_id' => $this->department->id]);
    $this->semester = Semester::factory()->create();
    $this->faculty = userWithRole(RoleName::Faculty->value, $this->department);
    $this->head = userWithRole(RoleName::DepartmentHead->value, $this->department);

    $this->section = ClassSection::factory()->create([
        'course_id' => $this->course->id,
        'semester_id' => $this->semester->id,
        'max_students' => 10,
    ]);
    $this->section->facultyAssignments()->create(['faculty_id' => $this->faculty->id, 'role' => 'primary']);

    $this->students = Student::factory()->count(2)->create(['department_id' => $this->department->id]);
    $this->enrollmentCourses = $this->students->map(function ($student) {
        $enrollment = StudentEnrollment::factory()->create(['student_id' => $student->id, 'semester_id' => $this->semester->id]);

        return EnrollmentCourse::factory()->create([
            'student_enrollment_id' => $enrollment->id,
            'class_section_id' => $this->section->id,
            'status' => 'Enrolled',
        ]);
    });
});

test('the assigned faculty can encode a grade and it is logged', function () {
    $ec = $this->enrollmentCourses->first();

    $response = $this->actingAs($this->faculty)->put("/class-sections/{$this->section->id}/grades/{$ec->id}", [
        'grade' => '1.00',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('student_grades', ['enrollment_course_id' => $ec->id, 'grade' => '1.00', 'status' => 'draft']);
    $studentGrade = StudentGrade::where('enrollment_course_id', $ec->id)->firstOrFail();
    $this->assertDatabaseHas('grade_change_logs', [
        'student_grade_id' => $studentGrade->id,
        'previous_grade' => null,
        'new_grade' => '1.00',
        'changed_by' => $this->faculty->id,
    ]);
});

test('a faculty member not assigned to the section cannot encode grades', function () {
    $otherFaculty = userWithRole(RoleName::Faculty->value, $this->department);
    $ec = $this->enrollmentCourses->first();

    $this->actingAs($otherFaculty)->put("/class-sections/{$this->section->id}/grades/{$ec->id}", [
        'grade' => '1.00',
    ])->assertForbidden();
});

test('submitting for review is rejected until every enrolled student has a grade', function () {
    $ec = $this->enrollmentCourses->first();
    $this->actingAs($this->faculty)->put("/class-sections/{$this->section->id}/grades/{$ec->id}", ['grade' => '1.00']);

    $response = $this->actingAs($this->faculty)->post("/class-sections/{$this->section->id}/grades/submit");

    $response->assertSessionHasErrors('submit');
    $this->assertDatabaseHas('grade_submissions', ['class_section_id' => $this->section->id, 'status' => 'draft']);
});

test('a fully graded class can be submitted, approved, and finalized', function () {
    foreach ($this->enrollmentCourses as $ec) {
        $this->actingAs($this->faculty)->put("/class-sections/{$this->section->id}/grades/{$ec->id}", ['grade' => '1.00']);
    }

    $this->actingAs($this->faculty)->post("/class-sections/{$this->section->id}/grades/submit")->assertRedirect();
    $this->assertDatabaseHas('grade_submissions', ['class_section_id' => $this->section->id, 'status' => 'submitted']);
    $this->assertDatabaseHas('student_grades', ['enrollment_course_id' => $this->enrollmentCourses->first()->id, 'status' => 'submitted']);

    $this->actingAs($this->head)->post("/class-sections/{$this->section->id}/grades/approve")->assertRedirect();
    $this->assertDatabaseHas('grade_submissions', ['class_section_id' => $this->section->id, 'status' => 'reviewed']);

    $this->actingAs($this->head)->post("/class-sections/{$this->section->id}/grades/finalize")->assertRedirect();
    $this->assertDatabaseHas('grade_submissions', ['class_section_id' => $this->section->id, 'status' => 'finalized']);
    $this->assertDatabaseHas('student_grades', ['enrollment_course_id' => $this->enrollmentCourses->first()->id, 'status' => 'finalized']);
});

test('a department head can return a submission for correction, unlocking grades for the faculty again', function () {
    foreach ($this->enrollmentCourses as $ec) {
        $this->actingAs($this->faculty)->put("/class-sections/{$this->section->id}/grades/{$ec->id}", ['grade' => '1.00']);
    }
    $this->actingAs($this->faculty)->post("/class-sections/{$this->section->id}/grades/submit");

    $this->actingAs($this->head)->post("/class-sections/{$this->section->id}/grades/return", [
        'remarks' => 'Please double-check row 2.',
    ])->assertRedirect();

    $this->assertDatabaseHas('grade_submissions', [
        'class_section_id' => $this->section->id,
        'status' => 'returned',
        'review_remarks' => 'Please double-check row 2.',
    ]);
    $this->assertDatabaseHas('student_grades', ['enrollment_course_id' => $this->enrollmentCourses->first()->id, 'status' => 'draft']);

    $ec = $this->enrollmentCourses->first();
    $this->actingAs($this->faculty)->put("/class-sections/{$this->section->id}/grades/{$ec->id}", ['grade' => '1.25'])
        ->assertRedirect();
    $this->assertDatabaseHas('student_grades', ['enrollment_course_id' => $ec->id, 'grade' => '1.25']);
});

test('grades cannot be finalized before being reviewed', function () {
    foreach ($this->enrollmentCourses as $ec) {
        $this->actingAs($this->faculty)->put("/class-sections/{$this->section->id}/grades/{$ec->id}", ['grade' => '1.00']);
    }
    $this->actingAs($this->faculty)->post("/class-sections/{$this->section->id}/grades/submit");

    $response = $this->actingAs($this->head)->post("/class-sections/{$this->section->id}/grades/finalize");

    $response->assertSessionHasErrors('finalize');
    $this->assertDatabaseHas('grade_submissions', ['class_section_id' => $this->section->id, 'status' => 'submitted']);
});

test('once finalized, a department head can correct a single grade with a reason and it is logged', function () {
    foreach ($this->enrollmentCourses as $ec) {
        $this->actingAs($this->faculty)->put("/class-sections/{$this->section->id}/grades/{$ec->id}", ['grade' => '1.00']);
    }
    $this->actingAs($this->faculty)->post("/class-sections/{$this->section->id}/grades/submit");
    $this->actingAs($this->head)->post("/class-sections/{$this->section->id}/grades/approve");
    $this->actingAs($this->head)->post("/class-sections/{$this->section->id}/grades/finalize");

    $ec = $this->enrollmentCourses->first();
    $studentGrade = StudentGrade::where('enrollment_course_id', $ec->id)->firstOrFail();

    $response = $this->actingAs($this->head)->patch("/class-sections/{$this->section->id}/grades/{$studentGrade->id}/correct", [
        'grade' => '1.25',
        'reason' => 'Averaging error found after finalization.',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('student_grades', ['id' => $studentGrade->id, 'grade' => '1.25', 'status' => 'finalized']);
    $this->assertDatabaseHas('grade_change_logs', [
        'student_grade_id' => $studentGrade->id,
        'previous_grade' => '1.00',
        'new_grade' => '1.25',
        'changed_by' => $this->head->id,
        'approved_by' => $this->head->id,
        'reason' => 'Averaging error found after finalization.',
    ]);
});

test('the assigned faculty cannot edit a grade directly once the submission is finalized', function () {
    foreach ($this->enrollmentCourses as $ec) {
        $this->actingAs($this->faculty)->put("/class-sections/{$this->section->id}/grades/{$ec->id}", ['grade' => '1.00']);
    }
    $this->actingAs($this->faculty)->post("/class-sections/{$this->section->id}/grades/submit");
    $this->actingAs($this->head)->post("/class-sections/{$this->section->id}/grades/approve");
    $this->actingAs($this->head)->post("/class-sections/{$this->section->id}/grades/finalize");

    $ec = $this->enrollmentCourses->first();
    $response = $this->actingAs($this->faculty)->put("/class-sections/{$this->section->id}/grades/{$ec->id}", ['grade' => '5.00']);

    $response->assertSessionHasErrors('grade');
    $this->assertDatabaseHas('student_grades', ['enrollment_course_id' => $ec->id, 'grade' => '1.00']);
});

test('a department head from another department cannot review or finalize this section', function () {
    $otherDepartment = Department::factory()->create();
    $otherHead = userWithRole(RoleName::DepartmentHead->value, $otherDepartment);

    foreach ($this->enrollmentCourses as $ec) {
        $this->actingAs($this->faculty)->put("/class-sections/{$this->section->id}/grades/{$ec->id}", ['grade' => '1.00']);
    }
    $this->actingAs($this->faculty)->post("/class-sections/{$this->section->id}/grades/submit");

    $this->actingAs($otherHead)->post("/class-sections/{$this->section->id}/grades/approve")->assertForbidden();
});

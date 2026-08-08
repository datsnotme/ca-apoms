<?php

use App\Enums\RoleName;
use App\Models\ClassSection;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\Department;
use App\Models\EnrollmentCourse;
use App\Models\GradeSubmission;
use App\Models\ImportBatch;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentGrade;
use App\Models\YearLevel;
use Database\Seeders\GradingScaleSeeder;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function makeXlsxUpload(array $headings, array $rows): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray($headings, null, 'A1');
    $sheet->fromArray($rows, null, 'A2');

    $path = storage_path('app/testing-import-'.uniqid().'.xlsx');
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

beforeEach(function () {
    $this->seed(GradingScaleSeeder::class);

    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->department = Department::factory()->create(['code' => 'CROPSCI']);
    $this->program = Program::factory()->create(['department_id' => $this->department->id, 'code' => 'BSA-CS']);
    $this->curriculum = Curriculum::factory()->create(['program_id' => $this->program->id, 'code' => 'BSA-CS-2026']);
    $this->yearLevel = YearLevel::factory()->create(['level' => 1, 'label' => '1st Year']);
});

afterEach(function () {
    foreach (glob(storage_path('app/testing-import-*.xlsx')) as $file) {
        @unlink($file);
    }
});

test('an admin can import students from an xlsx file', function () {
    $file = makeXlsxUpload(
        ['student_number', 'surname', 'first_name', 'middle_name', 'department_code', 'program_code', 'curriculum_code', 'year_level', 'classification', 'status'],
        [
            ['2026-00001', 'Dela Cruz', 'Juan', 'Santos', 'CROPSCI', 'BSA-CS', 'BSA-CS-2026', 1, 'regular', 'active'],
            ['2026-00002', 'Reyes', 'Maria', '', 'CROPSCI', 'BSA-CS', 'BSA-CS-2026', 1, 'regular', 'active'],
        ]
    );

    $response = $this->actingAs($this->admin)->post('/imports/STUDENTS', ['file' => $file]);

    $batch = ImportBatch::firstOrFail();
    $response->assertRedirect("/imports/batches/{$batch->id}");
    expect($batch->total_rows)->toBe(2);
    expect($batch->success_rows)->toBe(2);
    expect($batch->error_rows)->toBe(0);
    $this->assertDatabaseHas('students', ['student_number' => '2026-00001', 'surname' => 'Dela Cruz']);
    $this->assertDatabaseHas('students', ['student_number' => '2026-00002', 'surname' => 'Reyes']);
});

test('an invalid row is recorded as an error without blocking the rest of the file', function () {
    $file = makeXlsxUpload(
        ['student_number', 'surname', 'first_name', 'middle_name', 'department_code', 'program_code', 'curriculum_code', 'year_level', 'classification', 'status'],
        [
            ['2026-00001', 'Dela Cruz', 'Juan', '', 'CROPSCI', 'BSA-CS', 'BSA-CS-2026', 1, 'regular', 'active'],
            ['2026-00002', 'Reyes', 'Maria', '', 'NOPE', 'BSA-CS', 'BSA-CS-2026', 1, 'regular', 'active'],
        ]
    );

    $response = $this->actingAs($this->admin)->post('/imports/STUDENTS', ['file' => $file]);

    $batch = ImportBatch::firstOrFail();
    $response->assertRedirect("/imports/batches/{$batch->id}");
    expect($batch->total_rows)->toBe(2);
    expect($batch->success_rows)->toBe(1);
    expect($batch->error_rows)->toBe(1);
    $this->assertDatabaseHas('students', ['student_number' => '2026-00001']);
    $this->assertDatabaseMissing('students', ['student_number' => '2026-00002']);
    $this->assertDatabaseHas('import_batch_errors', ['import_batch_id' => $batch->id, 'row_number' => 3]);
});

test('a faculty member cannot import students but can import grades', function () {
    $faculty = userWithRole(RoleName::Faculty->value, $this->department);

    $file = makeXlsxUpload(['student_number'], [['2026-00001']]);
    $this->actingAs($faculty)->post('/imports/STUDENTS', ['file' => $file])->assertForbidden();
});

test('importing courses upserts by code', function () {
    $file = makeXlsxUpload(
        ['department_code', 'code', 'title', 'units', 'category', 'lecture_hours', 'laboratory_hours', 'is_active'],
        [['CROPSCI', 'AGRO101', 'Introduction to Crop Science', 3, 'crop_science', 3, 0, 1]]
    );

    $this->actingAs($this->admin)->post('/imports/COURSES', ['file' => $file])->assertRedirect();

    $this->assertDatabaseHas('courses', ['code' => 'AGRO101', 'title' => 'Introduction to Crop Science']);
});

test('importing curriculum courses links a course to a curriculum', function () {
    $course = Course::factory()->create(['department_id' => $this->department->id, 'code' => 'AGRO101']);

    $file = makeXlsxUpload(
        ['curriculum_code', 'course_code', 'year_level', 'semester', 'is_required', 'units', 'sequence_order'],
        [['BSA-CS-2026', 'AGRO101', 1, 'FIRST', 1, 3, 1]]
    );

    $this->actingAs($this->admin)->post('/imports/CURRICULUM_COURSES', ['file' => $file])->assertRedirect();

    $this->assertDatabaseHas('curriculum_courses', ['curriculum_id' => $this->curriculum->id, 'course_id' => $course->id]);
});

test('importing enrollment creates a student enrollment and enrollment course', function () {
    $course = Course::factory()->create(['department_id' => $this->department->id, 'code' => 'AGRO101']);
    $semester = Semester::factory()->create();
    $academicYear = $semester->academicYear;
    $section = ClassSection::factory()->create(['course_id' => $course->id, 'semester_id' => $semester->id, 'section_label' => 'A']);
    $student = Student::factory()->create([
        'student_number' => '2026-00001',
        'department_id' => $this->department->id,
        'program_id' => $this->program->id,
        'curriculum_id' => $this->curriculum->id,
        'year_level_id' => $this->yearLevel->id,
    ]);

    $file = makeXlsxUpload(
        ['student_number', 'school_year_start', 'school_year_end', 'semester', 'course_code', 'section_label'],
        [['2026-00001', $academicYear->start_year, $academicYear->end_year, $semester->term->value, 'AGRO101', 'A']]
    );

    $this->actingAs($this->admin)->post('/imports/ENROLLMENT', ['file' => $file])->assertRedirect();

    $enrollment = StudentEnrollment::where('student_id', $student->id)->where('semester_id', $semester->id)->first();
    expect($enrollment)->not->toBeNull();
    $this->assertDatabaseHas('enrollment_courses', ['student_enrollment_id' => $enrollment->id, 'class_section_id' => $section->id]);
});

test('importing grades encodes a grade for an enrolled student', function () {
    $course = Course::factory()->create(['department_id' => $this->department->id, 'code' => 'AGRO101']);
    $semester = Semester::factory()->create();
    $academicYear = $semester->academicYear;
    $section = ClassSection::factory()->create(['course_id' => $course->id, 'semester_id' => $semester->id, 'section_label' => 'A']);
    $student = Student::factory()->create([
        'student_number' => '2026-00001',
        'department_id' => $this->department->id,
        'program_id' => $this->program->id,
        'curriculum_id' => $this->curriculum->id,
        'year_level_id' => $this->yearLevel->id,
    ]);
    $enrollment = StudentEnrollment::factory()->create(['student_id' => $student->id, 'semester_id' => $semester->id]);
    $enrollmentCourse = EnrollmentCourse::factory()->create([
        'student_enrollment_id' => $enrollment->id,
        'class_section_id' => $section->id,
        'status' => 'Enrolled',
    ]);

    $file = makeXlsxUpload(
        ['student_number', 'course_code', 'section_label', 'school_year_start', 'school_year_end', 'semester', 'grade'],
        [['2026-00001', 'AGRO101', 'A', $academicYear->start_year, $academicYear->end_year, $semester->term->value, '1.00']]
    );

    $this->actingAs($this->admin)->post('/imports/GRADES', ['file' => $file])->assertRedirect();

    $this->assertDatabaseHas('student_grades', ['enrollment_course_id' => $enrollmentCourse->id, 'grade' => '1.00']);
});

test('importing a grade for a finalized submission is rejected as a row error', function () {
    $course = Course::factory()->create(['department_id' => $this->department->id, 'code' => 'AGRO101']);
    $semester = Semester::factory()->create();
    $academicYear = $semester->academicYear;
    $section = ClassSection::factory()->create(['course_id' => $course->id, 'semester_id' => $semester->id, 'section_label' => 'A']);
    $student = Student::factory()->create([
        'student_number' => '2026-00001',
        'department_id' => $this->department->id,
        'program_id' => $this->program->id,
        'curriculum_id' => $this->curriculum->id,
        'year_level_id' => $this->yearLevel->id,
    ]);
    $enrollment = StudentEnrollment::factory()->create(['student_id' => $student->id, 'semester_id' => $semester->id]);
    EnrollmentCourse::factory()->create([
        'student_enrollment_id' => $enrollment->id,
        'class_section_id' => $section->id,
        'status' => 'Enrolled',
    ]);
    GradeSubmission::factory()->create(['class_section_id' => $section->id, 'status' => 'finalized']);

    $file = makeXlsxUpload(
        ['student_number', 'course_code', 'section_label', 'school_year_start', 'school_year_end', 'semester', 'grade'],
        [['2026-00001', 'AGRO101', 'A', $academicYear->start_year, $academicYear->end_year, $semester->term->value, '1.00']]
    );

    $response = $this->actingAs($this->admin)->post('/imports/GRADES', ['file' => $file]);

    $batch = ImportBatch::firstOrFail();
    $response->assertRedirect("/imports/batches/{$batch->id}");
    expect($batch->success_rows)->toBe(0);
    expect($batch->error_rows)->toBe(1);
    expect(StudentGrade::count())->toBe(0);
});

test('a template can be downloaded for each import type', function () {
    $response = $this->actingAs($this->admin)->get('/imports/STUDENTS/template');

    $response->assertOk();
    $response->assertHeader('content-disposition');
});

test('a user without the matching permission cannot view or download errors for a batch', function () {
    $batch = ImportBatch::factory()->create(['type' => 'STUDENTS', 'uploaded_by' => $this->admin->id]);
    $faculty = userWithRole(RoleName::Faculty->value, $this->department);

    $this->actingAs($faculty)->get("/imports/batches/{$batch->id}")->assertForbidden();
    $this->actingAs($faculty)->get("/imports/batches/{$batch->id}/errors")->assertForbidden();
});

test('a user with the matching permission can view a batch and its errors', function () {
    $batch = ImportBatch::factory()->create(['type' => 'GRADES', 'uploaded_by' => $this->admin->id]);
    $faculty = userWithRole(RoleName::Faculty->value, $this->department);

    $this->actingAs($faculty)->get("/imports/batches/{$batch->id}")->assertOk();
    $this->actingAs($faculty)->get("/imports/batches/{$batch->id}/errors")->assertOk();
});

test('the import index only lists batches for types the user can access', function () {
    ImportBatch::factory()->create(['type' => 'STUDENTS', 'uploaded_by' => $this->admin->id]);
    ImportBatch::factory()->create(['type' => 'GRADES', 'uploaded_by' => $this->admin->id]);
    $faculty = userWithRole(RoleName::Faculty->value, $this->department);

    $response = $this->actingAs($faculty)->get('/imports');

    $response->assertOk()->assertInertia(fn ($page) => $page->has('batches.data', 1));
});

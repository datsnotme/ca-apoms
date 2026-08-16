<?php

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\Student;
use App\Models\StudentHistoricalGrade;
use App\Services\StudentEvaluationService;
use Database\Seeders\GradingScaleSeeder;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function makeHistoricalGradeUpload(array $rows): UploadedFile
{
    $headings = ['student_number', 'student_name', 'academic_year', 'semester', 'program', 'course_code', 'course_title', 'lecture_hours', 'laboratory_hours', 'units', 'grade'];

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray($headings, null, 'A1');
    $sheet->fromArray($rows, null, 'A2');

    $path = storage_path('app/testing-historical-'.uniqid().'.xlsx');
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

beforeEach(function () {
    $this->seed(GradingScaleSeeder::class);

    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->department = Department::factory()->create();
    $this->student = Student::factory()->create([
        'department_id' => $this->department->id,
        'student_number' => '220651-04',
        'surname' => 'Julhatam',
        'first_name' => 'Ranijane',
        'middle_name' => 'S.',
    ]);
});

afterEach(function () {
    foreach (glob(storage_path('app/testing-historical-*.xlsx')) as $file) {
        @unlink($file);
    }
});

test('an admin can import a students historical grade record', function () {
    $file = makeHistoricalGradeUpload([
        ['220651-04', 'JULHATAM, RANIJANE S.', '2022-2023', 'First Semester', 'BSBIO', 'GEC101', 'Understanding the Self', 3, 0, 3, '2.5'],
        ['220651-04', 'JULHATAM, RANIJANE S.', '2022-2023', 'First Semester', 'BSBIO', 'BIO101', 'General Zoology', 3, 2, 5, 'INC'],
    ]);

    $response = $this->actingAs($this->admin)->post("/students/{$this->student->id}/historical-grades", ['file' => $file]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseCount('student_historical_grades', 2);
    $this->assertDatabaseHas('student_historical_grades', [
        'student_id' => $this->student->id,
        'course_code' => 'GEC101',
        'grade' => '2.50',
    ]);
});

test('re-uploading replaces the previously imported record instead of appending', function () {
    StudentHistoricalGrade::factory()->count(3)->create(['student_id' => $this->student->id]);

    $file = makeHistoricalGradeUpload([
        ['220651-04', 'JULHATAM, RANIJANE S.', '2024-2025', 'Second Semester', 'BSA-PATIKUL', 'AGR003', 'Introduction to Methods of Agriculture Research', 2, 3, 3, '2.75'],
    ]);

    $this->actingAs($this->admin)->post("/students/{$this->student->id}/historical-grades", ['file' => $file]);

    $this->assertDatabaseCount('student_historical_grades', 1);
    $this->assertDatabaseHas('student_historical_grades', ['course_code' => 'AGR003']);
});

test('a row with a mismatched student number rejects the whole upload', function () {
    $file = makeHistoricalGradeUpload([
        ['220651-04', 'JULHATAM, RANIJANE S.', '2022-2023', 'First Semester', 'BSBIO', 'GEC101', 'Understanding the Self', 3, 0, 3, '2.5'],
        ['999999-99', 'SOMEONE ELSE', '2022-2023', 'First Semester', 'BSBIO', 'BIO101', 'General Zoology', 3, 2, 5, 'INC'],
    ]);

    $response = $this->actingAs($this->admin)->post("/students/{$this->student->id}/historical-grades", ['file' => $file]);

    $response->assertSessionHasErrors('file');
    $this->assertDatabaseCount('student_historical_grades', 0);
});

test('a row with a mismatched student name rejects the whole upload even if the number matches', function () {
    $file = makeHistoricalGradeUpload([
        ['220651-04', 'DELA CRUZ, JUAN P.', '2022-2023', 'First Semester', 'BSBIO', 'GEC101', 'Understanding the Self', 3, 0, 3, '2.5'],
    ]);

    $response = $this->actingAs($this->admin)->post("/students/{$this->student->id}/historical-grades", ['file' => $file]);

    $response->assertSessionHasErrors('file');
    $this->assertDatabaseCount('student_historical_grades', 0);
});

test('a name with different formatting or a middle initial still matches', function () {
    $file = makeHistoricalGradeUpload([
        ['220651-04', 'Ranijane S. Julhatam', '2022-2023', 'First Semester', 'BSBIO', 'GEC101', 'Understanding the Self', 3, 0, 3, '2.5'],
    ]);

    $response = $this->actingAs($this->admin)->post("/students/{$this->student->id}/historical-grades", ['file' => $file]);

    $response->assertSessionDoesntHaveErrors('file');
    $this->assertDatabaseCount('student_historical_grades', 1);
});

test('a department head without grades.import permission cannot upload', function () {
    $head = userWithRole(RoleName::DepartmentHead->value, $this->department);

    $file = makeHistoricalGradeUpload([
        ['220651-04', 'JULHATAM, RANIJANE S.', '2022-2023', 'First Semester', 'BSBIO', 'GEC101', 'Understanding the Self', 3, 0, 3, '2.5'],
    ]);

    $this->actingAs($head)->post("/students/{$this->student->id}/historical-grades", ['file' => $file])->assertForbidden();
    $this->assertDatabaseCount('student_historical_grades', 0);
});

test('the template downloads for an authorized user', function () {
    $this->actingAs($this->admin)->get("/students/{$this->student->id}/historical-grades/template")->assertOk();
});

test('the evaluation includes the prior academic record grouped by term, without affecting gwa or summary totals', function () {
    StudentHistoricalGrade::factory()->create([
        'student_id' => $this->student->id,
        'academic_year_label' => '2022-2023',
        'semester_label' => 'First Semester',
        'program_label' => 'BSBIO',
        'course_code' => 'GEC101',
        'units' => 3,
        'grade' => '2.50',
    ]);
    StudentHistoricalGrade::factory()->create([
        'student_id' => $this->student->id,
        'academic_year_label' => '2022-2023',
        'semester_label' => 'First Semester',
        'program_label' => 'BSBIO',
        'course_code' => 'BIO101',
        'units' => 5,
        'grade' => 'INC',
    ]);

    $service = app(StudentEvaluationService::class);
    $result = $service->evaluate($this->student->fresh());

    expect($result['prior_academic_record'])->toHaveCount(1);
    expect($result['prior_academic_record'][0]['rows'])->toHaveCount(2);
    expect($result['prior_academic_record'][0]['total_units'])->toBe(8.0);

    // Historical/imported units must never bleed into the current
    // curriculum's own accounting.
    expect($result['gwa'])->toBeNull();
    expect($result['summary']['total_units_required'])->toBe(0.0);
});

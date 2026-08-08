<?php

use App\Enums\RoleName;
use App\Models\Curriculum;
use App\Models\Department;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\YearLevel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->department = Department::factory()->create();
    $this->program = Program::factory()->create(['department_id' => $this->department->id]);
    $this->curriculum = Curriculum::factory()->create(['program_id' => $this->program->id]);
    $this->yearLevel = YearLevel::factory()->create();
    $this->student = Student::factory()->create([
        'department_id' => $this->department->id,
        'program_id' => $this->program->id,
        'curriculum_id' => $this->curriculum->id,
        'year_level_id' => $this->yearLevel->id,
    ]);
});

test('an admin can upload a document for a student', function () {
    $file = UploadedFile::fake()->create('birth-certificate.pdf', 500, 'application/pdf');

    $response = $this->actingAs($this->admin)->post("/students/{$this->student->id}/documents", [
        'category' => 'birth_certificate',
        'title' => 'Birth Certificate',
        'file' => $file,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('student_documents', [
        'student_id' => $this->student->id,
        'category' => 'birth_certificate',
        'title' => 'Birth Certificate',
        'verification_status' => 'pending',
    ]);

    $document = StudentDocument::first();
    Storage::disk('local')->assertExists($document->file_path);
});

test('a document upload is rejected when the file type is not allowed', function () {
    $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

    $response = $this->actingAs($this->admin)->post("/students/{$this->student->id}/documents", [
        'category' => 'birth_certificate',
        'title' => 'Suspicious File',
        'file' => $file,
    ]);

    $response->assertSessionHasErrors('file');
});

test('an admin can verify a pending document', function () {
    $document = StudentDocument::factory()->create(['student_id' => $this->student->id]);

    $response = $this->actingAs($this->admin)->patch(
        "/students/{$this->student->id}/documents/{$document->id}/verify",
        ['verification_status' => 'verified', 'remarks' => 'Matches records']
    );

    $response->assertRedirect();
    $this->assertDatabaseHas('student_documents', [
        'id' => $document->id,
        'verification_status' => 'verified',
        'verified_by' => $this->admin->id,
        'remarks' => 'Matches records',
    ]);
});

test('a department head can view but not upload or verify documents', function () {
    $head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $document = StudentDocument::factory()->create(['student_id' => $this->student->id]);
    Storage::disk('local')->put($document->file_path, 'contents');

    $file = UploadedFile::fake()->create('form.pdf', 200, 'application/pdf');

    $this->actingAs($head)->post("/students/{$this->student->id}/documents", [
        'category' => 'form_137', 'title' => 'Form 137', 'file' => $file,
    ])->assertForbidden();

    $this->actingAs($head)->patch(
        "/students/{$this->student->id}/documents/{$document->id}/verify",
        ['verification_status' => 'verified']
    )->assertForbidden();

    $this->actingAs($head)->get("/students/{$this->student->id}/documents/{$document->id}/download")->assertOk();
});

test('a department head cannot access documents from another department', function () {
    $otherDepartment = Department::factory()->create();
    $head = userWithRole(RoleName::DepartmentHead->value, $otherDepartment);
    $document = StudentDocument::factory()->create(['student_id' => $this->student->id]);

    $this->actingAs($head)->get("/students/{$this->student->id}/documents/{$document->id}/download")->assertForbidden();
});

test('a faculty member has no access to student documents', function () {
    $faculty = userWithRole(RoleName::Faculty->value, $this->department);
    $document = StudentDocument::factory()->create(['student_id' => $this->student->id]);

    $this->actingAs($faculty)->get("/students/{$this->student->id}/documents/{$document->id}/download")->assertForbidden();
});

test('deleting a document removes the stored file', function () {
    $document = StudentDocument::factory()->create([
        'student_id' => $this->student->id,
        'file_path' => 'student-documents/'.$this->student->id.'/sample.pdf',
    ]);
    Storage::disk('local')->put($document->file_path, 'contents');

    $this->actingAs($this->admin)->delete("/students/{$this->student->id}/documents/{$document->id}")
        ->assertRedirect();

    Storage::disk('local')->assertMissing($document->file_path);
    $this->assertSoftDeleted('student_documents', ['id' => $document->id]);
});

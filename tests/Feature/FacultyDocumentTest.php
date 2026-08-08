<?php

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\FacultyDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->department = Department::factory()->create();
    $this->head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $this->faculty = userWithRole(RoleName::Faculty->value, $this->department);
});

test('an admin can upload a document for any faculty member', function () {
    $file = UploadedFile::fake()->create('diploma.pdf', 500, 'application/pdf');

    $response = $this->actingAs($this->admin)->post("/faculty-profiles/{$this->faculty->id}/documents", [
        'category' => 'diploma',
        'title' => 'Diploma',
        'file' => $file,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('faculty_documents', [
        'user_id' => $this->faculty->id,
        'category' => 'diploma',
        'verification_status' => 'pending',
    ]);

    $document = FacultyDocument::first();
    Storage::disk('local')->assertExists($document->file_path);
});

test('a faculty member can upload their own document', function () {
    $file = UploadedFile::fake()->create('license.pdf', 300, 'application/pdf');

    $response = $this->actingAs($this->faculty)->post("/faculty-profiles/{$this->faculty->id}/documents", [
        'category' => 'professional_license',
        'title' => 'PRC License',
        'file' => $file,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('faculty_documents', [
        'user_id' => $this->faculty->id,
        'category' => 'professional_license',
        'uploaded_by' => $this->faculty->id,
        'verification_status' => 'pending',
    ]);
});

test('a faculty member cannot upload a document for another faculty member', function () {
    $otherFaculty = userWithRole(RoleName::Faculty->value, $this->department);
    $file = UploadedFile::fake()->create('diploma.pdf', 300, 'application/pdf');

    $this->actingAs($this->faculty)->post("/faculty-profiles/{$otherFaculty->id}/documents", [
        'category' => 'diploma',
        'title' => 'Diploma',
        'file' => $file,
    ])->assertForbidden();
});

test('a document upload is rejected when the file type is not allowed', function () {
    $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

    $this->actingAs($this->admin)->post("/faculty-profiles/{$this->faculty->id}/documents", [
        'category' => 'diploma',
        'title' => 'Suspicious File',
        'file' => $file,
    ])->assertSessionHasErrors('file');
});

test('an admin can verify a pending document but a department head cannot', function () {
    $document = FacultyDocument::factory()->create(['user_id' => $this->faculty->id]);

    $this->actingAs($this->head)->patch(
        "/faculty-profiles/{$this->faculty->id}/documents/{$document->id}/verify",
        ['verification_status' => 'verified']
    )->assertForbidden();

    $response = $this->actingAs($this->admin)->patch(
        "/faculty-profiles/{$this->faculty->id}/documents/{$document->id}/verify",
        ['verification_status' => 'verified', 'remarks' => 'Confirmed with HR.']
    );

    $response->assertRedirect();
    $this->assertDatabaseHas('faculty_documents', [
        'id' => $document->id,
        'verification_status' => 'verified',
        'verified_by' => $this->admin->id,
        'remarks' => 'Confirmed with HR.',
    ]);
});

test('a faculty member cannot verify their own uploaded document', function () {
    $document = FacultyDocument::factory()->create(['user_id' => $this->faculty->id]);

    $this->actingAs($this->faculty)->patch(
        "/faculty-profiles/{$this->faculty->id}/documents/{$document->id}/verify",
        ['verification_status' => 'verified']
    )->assertForbidden();
});

test('a department head can view and download but not upload or verify', function () {
    $document = FacultyDocument::factory()->create(['user_id' => $this->faculty->id]);
    Storage::disk('local')->put($document->file_path, 'contents');

    $this->actingAs($this->head)->get("/faculty-profiles/{$this->faculty->id}/documents/{$document->id}/download")
        ->assertOk();

    $file = UploadedFile::fake()->create('diploma.pdf', 300, 'application/pdf');
    $this->actingAs($this->head)->post("/faculty-profiles/{$this->faculty->id}/documents", [
        'category' => 'diploma', 'title' => 'Diploma', 'file' => $file,
    ])->assertForbidden();
});

test('a department head cannot access documents from another department', function () {
    $otherDepartment = Department::factory()->create();
    $head = userWithRole(RoleName::DepartmentHead->value, $otherDepartment);
    $document = FacultyDocument::factory()->create(['user_id' => $this->faculty->id]);

    $this->actingAs($head)->get("/faculty-profiles/{$this->faculty->id}/documents/{$document->id}/download")
        ->assertForbidden();
});

test('a faculty member cannot view another faculty member document', function () {
    $otherFaculty = userWithRole(RoleName::Faculty->value, $this->department);
    $document = FacultyDocument::factory()->create(['user_id' => $this->faculty->id]);

    $this->actingAs($otherFaculty)->get("/faculty-profiles/{$this->faculty->id}/documents/{$document->id}/download")
        ->assertForbidden();
});

test('deleting a document removes the stored file and is admin-only', function () {
    $document = FacultyDocument::factory()->create([
        'user_id' => $this->faculty->id,
        'file_path' => 'faculty-documents/'.$this->faculty->id.'/sample.pdf',
    ]);
    Storage::disk('local')->put($document->file_path, 'contents');

    $this->actingAs($this->faculty)->delete("/faculty-profiles/{$this->faculty->id}/documents/{$document->id}")
        ->assertForbidden();

    $this->actingAs($this->admin)->delete("/faculty-profiles/{$this->faculty->id}/documents/{$document->id}")
        ->assertRedirect();

    Storage::disk('local')->assertMissing($document->file_path);
    $this->assertSoftDeleted('faculty_documents', ['id' => $document->id]);
});

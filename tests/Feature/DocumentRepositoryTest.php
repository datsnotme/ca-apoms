<?php

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $this->department = Department::factory()->create();
    $this->otherDepartment = Department::factory()->create();
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->dean = userWithRole(RoleName::Dean->value);
    $this->head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $this->faculty = userWithRole(RoleName::Faculty->value, $this->department);
    $this->otherFaculty = userWithRole(RoleName::Faculty->value, $this->otherDepartment);
    $this->category = DocumentCategory::factory()->create();
});

// --- Categories ---

test('an admin can create a document category but a department head cannot', function () {
    $this->actingAs($this->admin)->post('/document-categories', ['name' => 'Policies'])
        ->assertRedirect();
    $this->assertDatabaseHas('document_categories', ['name' => 'Policies']);

    $this->actingAs($this->head)->post('/document-categories', ['name' => 'Forms'])
        ->assertForbidden();
});

test('a category still in use cannot be deleted', function () {
    Document::factory()->create(['document_category_id' => $this->category->id, 'uploaded_by' => $this->admin->id]);

    $response = $this->actingAs($this->admin)->delete("/document-categories/{$this->category->id}");

    $response->assertRedirect();
    $this->assertDatabaseHas('document_categories', ['id' => $this->category->id]);
});

// --- Documents ---

test('an admin can upload a college-wide document', function () {
    $file = UploadedFile::fake()->create('policy.pdf', 500, 'application/pdf');

    $response = $this->actingAs($this->admin)->post('/documents', [
        'document_category_id' => $this->category->id,
        'title' => 'Attendance Policy',
        'department_id' => '',
        'file' => $file,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('documents', ['title' => 'Attendance Policy', 'department_id' => null]);

    $document = Document::first();
    $this->assertDatabaseHas('document_versions', ['document_id' => $document->id, 'version_number' => 1]);
    Storage::disk('local')->assertExists($document->versions->first()->file_path);
});

test('a department head can only upload a document scoped to their own department', function () {
    $file = UploadedFile::fake()->create('minutes.pdf', 300, 'application/pdf');

    $response = $this->actingAs($this->head)->post('/documents', [
        'document_category_id' => $this->category->id,
        'title' => 'Meeting Minutes',
        'department_id' => $this->otherDepartment->id,
        'file' => $file,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('documents', ['title' => 'Meeting Minutes', 'department_id' => $this->department->id]);
});

test('a dean and a faculty member cannot upload a document', function () {
    $file = UploadedFile::fake()->create('x.pdf', 100, 'application/pdf');

    $this->actingAs($this->dean)->post('/documents', [
        'document_category_id' => $this->category->id, 'title' => 'X', 'department_id' => '', 'file' => $file,
    ])->assertForbidden();

    $this->actingAs($this->faculty)->post('/documents', [
        'document_category_id' => $this->category->id, 'title' => 'X', 'department_id' => '', 'file' => $file,
    ])->assertForbidden();
});

test('a college-wide document is visible to everyone, a department document only to that department', function () {
    Document::factory()->create(['document_category_id' => $this->category->id, 'department_id' => null, 'uploaded_by' => $this->admin->id]);
    Document::factory()->create(['document_category_id' => $this->category->id, 'department_id' => $this->department->id, 'uploaded_by' => $this->admin->id]);

    $this->actingAs($this->otherFaculty)->get('/documents')
        ->assertInertia(fn ($page) => $page->has('documents.data', 1));

    $this->actingAs($this->faculty)->get('/documents')
        ->assertInertia(fn ($page) => $page->has('documents.data', 2));
});

test('a department head cannot edit a college-wide document or another departments document', function () {
    $collegeWide = Document::factory()->create(['document_category_id' => $this->category->id, 'department_id' => null, 'uploaded_by' => $this->admin->id]);
    $otherDept = Document::factory()->create(['document_category_id' => $this->category->id, 'department_id' => $this->otherDepartment->id, 'uploaded_by' => $this->admin->id]);

    $this->actingAs($this->head)->put("/documents/{$collegeWide->id}", [
        'document_category_id' => $this->category->id, 'title' => 'Updated', 'department_id' => '',
    ])->assertForbidden();

    $this->actingAs($this->head)->put("/documents/{$otherDept->id}", [
        'document_category_id' => $this->category->id, 'title' => 'Updated', 'department_id' => $this->otherDepartment->id,
    ])->assertForbidden();
});

test('deleting a document removes its stored files', function () {
    $file = UploadedFile::fake()->create('doc.pdf', 300, 'application/pdf');
    $this->actingAs($this->admin)->post('/documents', [
        'document_category_id' => $this->category->id, 'title' => 'Doc', 'department_id' => '', 'file' => $file,
    ]);
    $document = Document::first();
    $path = $document->versions->first()->file_path;

    $this->actingAs($this->faculty)->delete("/documents/{$document->id}")->assertForbidden();

    $this->actingAs($this->admin)->delete("/documents/{$document->id}")->assertRedirect();
    Storage::disk('local')->assertMissing($path);
    $this->assertSoftDeleted('documents', ['id' => $document->id]);
});

test('an admin can bulk remove multiple documents at once, deleting their stored files', function () {
    $paths = [];
    foreach (range(1, 3) as $i) {
        $file = UploadedFile::fake()->create("doc{$i}.pdf", 200, 'application/pdf');
        $this->actingAs($this->admin)->post('/documents', [
            'document_category_id' => $this->category->id, 'title' => "Doc {$i}", 'department_id' => '', 'file' => $file,
        ]);
    }
    $documents = Document::all();
    $paths = $documents->flatMap(fn (Document $d) => $d->versions->pluck('file_path'))->all();

    $response = $this->actingAs($this->admin)->delete('/documents/bulk-destroy', [
        'ids' => $documents->pluck('id')->all(),
    ]);

    $response->assertRedirect('/documents')->assertSessionHas('success', '3 document(s) removed.');
    $documents->each(fn (Document $d) => $this->assertSoftDeleted('documents', ['id' => $d->id]));
    foreach ($paths as $path) {
        Storage::disk('local')->assertMissing($path);
    }
});

test('bulk-deleting documents is scoped the same way as deleting one', function () {
    $deptDoc = Document::factory()->create(['document_category_id' => $this->category->id, 'department_id' => $this->department->id, 'uploaded_by' => $this->admin->id]);
    $otherDeptDoc = Document::factory()->create(['document_category_id' => $this->category->id, 'department_id' => $this->otherDepartment->id, 'uploaded_by' => $this->admin->id]);

    $this->actingAs($this->head)->delete('/documents/bulk-destroy', [
        'ids' => [$deptDoc->id, $otherDeptDoc->id],
    ])->assertForbidden();

    $this->assertDatabaseHas('documents', ['id' => $deptDoc->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('documents', ['id' => $otherDeptDoc->id, 'deleted_at' => null]);
});

// --- Versions ---

test('uploading a new version increments the version number', function () {
    $document = Document::factory()->create(['document_category_id' => $this->category->id, 'department_id' => $this->department->id, 'uploaded_by' => $this->admin->id]);
    $document->versions()->create([
        'version_number' => 1, 'file_path' => 'documents/1/v1.pdf', 'original_filename' => 'v1.pdf',
        'file_type' => 'application/pdf', 'file_size' => 100, 'uploaded_by' => $this->admin->id, 'uploaded_at' => now(),
    ]);

    $file = UploadedFile::fake()->create('v2.pdf', 400, 'application/pdf');
    $response = $this->actingAs($this->head)->post("/documents/{$document->id}/versions", [
        'file' => $file,
        'notes' => 'Updated figures',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('document_versions', ['document_id' => $document->id, 'version_number' => 2, 'notes' => 'Updated figures']);
});

test('the last remaining version of a document cannot be deleted', function () {
    $document = Document::factory()->create(['document_category_id' => $this->category->id, 'department_id' => $this->department->id, 'uploaded_by' => $this->admin->id]);
    $version = $document->versions()->create([
        'version_number' => 1, 'file_path' => 'documents/1/v1.pdf', 'original_filename' => 'v1.pdf',
        'file_type' => 'application/pdf', 'file_size' => 100, 'uploaded_by' => $this->admin->id, 'uploaded_at' => now(),
    ]);

    $this->actingAs($this->head)->delete("/documents/{$document->id}/versions/{$version->id}")->assertRedirect();
    $this->assertDatabaseHas('document_versions', ['id' => $version->id]);
});

test('a faculty member can download a document version but cannot upload a new one', function () {
    $document = Document::factory()->create(['document_category_id' => $this->category->id, 'department_id' => $this->department->id, 'uploaded_by' => $this->admin->id]);
    $version = $document->versions()->create([
        'version_number' => 1, 'file_path' => 'documents/1/v1.pdf', 'original_filename' => 'v1.pdf',
        'file_type' => 'application/pdf', 'file_size' => 100, 'uploaded_by' => $this->admin->id, 'uploaded_at' => now(),
    ]);
    Storage::disk('local')->put($version->file_path, 'contents');

    $this->actingAs($this->faculty)->get("/documents/{$document->id}/versions/{$version->id}/download")->assertOk();

    $file = UploadedFile::fake()->create('v2.pdf', 300, 'application/pdf');
    $this->actingAs($this->faculty)->post("/documents/{$document->id}/versions", ['file' => $file])->assertForbidden();
});

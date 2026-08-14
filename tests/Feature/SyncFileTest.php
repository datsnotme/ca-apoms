<?php

use App\Enums\RoleName;
use App\Models\College;
use App\Models\Curriculum;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentVersion;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\YearLevel;
use App\Services\SyncService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

/**
 * File/document sync — the binary half of the sync engine (row/DB sync for
 * these tables is proven generically the same way every other synced table
 * is; these tests focus on what's actually new: hash-based change
 * detection, the uuid-keyed path rewrite on receipt, and the download/
 * upload transfer orchestration + endpoints).
 */
beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->service = app(SyncService::class);
});

test('FacultyDocument is deliberately excluded from the synced table set', function () {
    expect($this->service->modelFor('faculty_documents'))->toBeNull();
    expect($this->service->fileColumnFor('faculty_documents'))->toBeNull();
});

test('colleges, document_versions, and student_documents are registered as file-bearing', function () {
    expect($this->service->fileColumnFor('colleges'))->toBe(['column' => 'logo_path', 'disk' => 'public']);
    expect($this->service->fileColumnFor('document_versions'))->toBe(['column' => 'file_path', 'disk' => 'local']);
    expect($this->service->fileColumnFor('student_documents'))->toBe(['column' => 'file_path', 'disk' => 'local']);
    expect($this->service->fileColumnFor('documents'))->toBeNull(); // metadata only, no file column of its own
});

test('a Document change resolves document_category_id and department_id via uuid', function () {
    $localCategory = DocumentCategory::factory()->create();
    $localDepartment = Department::factory()->create();
    $documentUuid = (string) Str::uuid();

    $change = [
        'entity_table' => 'documents',
        'entity_uuid' => $documentUuid,
        'operation' => 'created',
        'version' => 1,
        'snapshot' => [
            'document_category_id' => $localCategory->id + 999,
            'department_id' => $localDepartment->id + 999,
            'title' => 'Remote Document', 'description' => null, 'uploaded_by' => $this->admin->id,
            'uuid' => $documentUuid, 'sync_version' => 1,
            '_fk_uuids' => ['document_category_id' => $localCategory->uuid, 'department_id' => $localDepartment->uuid],
        ],
    ];

    $this->service->applyIncoming([$change]);

    $document = Document::where('uuid', $documentUuid)->firstOrFail();
    expect($document->document_category_id)->toBe($localCategory->id);
    expect($document->department_id)->toBe($localDepartment->id);
});

test('a DocumentVersion change resolves document_id via uuid and rewrites file_path to the synced convention', function () {
    $localDocument = Document::factory()->create();
    $versionUuid = (string) Str::uuid();

    $change = [
        'entity_table' => 'document_versions',
        'entity_uuid' => $versionUuid,
        'operation' => 'created',
        'version' => 1,
        'snapshot' => [
            'document_id' => $localDocument->id + 999,
            'version_number' => 1,
            'file_path' => 'documents/999/sender-only-path.pdf', // the sender's own, non-portable path — must be ignored
            'original_filename' => 'report.pdf', 'file_type' => 'application/pdf', 'file_size' => 12345,
            'notes' => null, 'uploaded_by' => $this->admin->id, 'uploaded_at' => now()->toIso8601String(),
            'uuid' => $versionUuid, 'sync_version' => 1,
            '_fk_uuids' => ['document_id' => $localDocument->uuid],
        ],
    ];

    $this->service->applyIncoming([$change]);

    $version = DocumentVersion::where('uuid', $versionUuid)->firstOrFail();
    expect($version->document_id)->toBe($localDocument->id);
    expect($version->file_path)->toBe("synced/document_versions/{$versionUuid}");
});

test('a StudentDocument change resolves student_id via uuid', function () {
    $college = College::factory()->create();
    $department = Department::factory()->create(['college_id' => $college->id]);
    $program = Program::factory()->create(['department_id' => $department->id]);
    $curriculum = Curriculum::factory()->create(['program_id' => $program->id]);
    $yearLevel = YearLevel::factory()->create();
    $localStudent = Student::factory()->create([
        'department_id' => $department->id, 'program_id' => $program->id,
        'curriculum_id' => $curriculum->id, 'year_level_id' => $yearLevel->id,
    ]);
    $docUuid = (string) Str::uuid();

    $change = [
        'entity_table' => 'student_documents',
        'entity_uuid' => $docUuid,
        'operation' => 'created',
        'version' => 1,
        'snapshot' => [
            'student_id' => $localStudent->id + 999,
            'category' => 'birth_certificate', 'title' => 'Birth Certificate',
            'file_path' => 'student-documents/999/x.pdf', 'original_filename' => 'x.pdf',
            'file_type' => 'application/pdf', 'file_size' => 100, 'uploaded_by' => null,
            'uploaded_at' => now()->toIso8601String(), 'verification_status' => 'pending',
            'verified_by' => null, 'verified_at' => null, 'remarks' => null, 'visibility_level' => 'staff_only',
            'uuid' => $docUuid, 'sync_version' => 1,
            '_fk_uuids' => ['student_id' => $localStudent->uuid],
        ],
    ];

    $this->service->applyIncoming([$change]);

    $doc = StudentDocument::where('uuid', $docUuid)->firstOrFail();
    expect($doc->student_id)->toBe($localStudent->id);
    expect($doc->file_path)->toBe("synced/student_documents/{$docUuid}");
});

test('snapshotFor computes a _file_hash for a file-bearing row with real content, and omits it when there is none', function () {
    $withLogo = College::factory()->create();
    Storage::disk('public')->put('branding/logo.jpg', 'the-logo-bytes');
    $withLogo->update(['logo_path' => 'branding/logo.jpg']);

    $withoutLogo = College::factory()->create(['logo_path' => null]);

    $result = $this->service->pendingChangesSince(0);
    $changes = collect($result['changes']);

    $withLogoChange = $changes->firstWhere('entity_uuid', $withLogo->uuid);
    expect($withLogoChange['snapshot']['_file_hash'])->toBe(hash('sha256', 'the-logo-bytes'));

    $withoutLogoChange = $changes->firstWhere('entity_uuid', $withoutLogo->uuid);
    expect($withoutLogoChange['snapshot'])->not->toHaveKey('_file_hash');
});

test('downloadMissingFiles fetches and stores bytes when the local file does not match the incoming hash', function () {
    $uuid = (string) Str::uuid();
    $bytes = 'the-actual-remote-file-bytes';
    Http::fake(['*/api/sync/files/*' => Http::response($bytes, 200)]);

    $counts = $this->service->downloadMissingFiles([
        ['entity_table' => 'student_documents', 'entity_uuid' => $uuid, 'operation' => 'created', 'snapshot' => ['_file_hash' => hash('sha256', $bytes)]],
    ], 'https://remote.test', 'tok');

    expect($counts)->toBe(['downloaded' => 1, 'skipped' => 0, 'failed' => 0]);
    expect(Storage::disk('local')->get("synced/student_documents/{$uuid}"))->toBe($bytes);
});

test('downloadMissingFiles skips when the local file already matches the incoming hash', function () {
    $uuid = (string) Str::uuid();
    $bytes = 'already-have-this';
    Storage::disk('local')->put("synced/student_documents/{$uuid}", $bytes);
    Http::fake(['*/api/sync/files/*' => Http::response('should not be requested', 200)]);

    $counts = $this->service->downloadMissingFiles([
        ['entity_table' => 'student_documents', 'entity_uuid' => $uuid, 'operation' => 'created', 'snapshot' => ['_file_hash' => hash('sha256', $bytes)]],
    ], 'https://remote.test', 'tok');

    expect($counts)->toBe(['downloaded' => 0, 'skipped' => 1, 'failed' => 0]);
    Http::assertNothingSent();
});

test('downloadMissingFiles counts a transfer failure without throwing, and does not touch local disk', function () {
    $uuid = (string) Str::uuid();
    Http::fake(['*/api/sync/files/*' => Http::response('server error', 500)]);

    $counts = $this->service->downloadMissingFiles([
        ['entity_table' => 'student_documents', 'entity_uuid' => $uuid, 'operation' => 'created', 'snapshot' => ['_file_hash' => hash('sha256', 'whatever')]],
    ], 'https://remote.test', 'tok');

    expect($counts)->toBe(['downloaded' => 0, 'skipped' => 0, 'failed' => 1]);
    Storage::disk('local')->assertMissing("synced/student_documents/{$uuid}");
});

test('downloadMissingFiles skips updates that did not touch the file column', function () {
    $uuid = (string) Str::uuid();
    Http::fake(['*/api/sync/files/*' => Http::response('should not be requested', 200)]);

    $counts = $this->service->downloadMissingFiles([
        ['entity_table' => 'student_documents', 'entity_uuid' => $uuid, 'operation' => 'updated', 'changed_fields' => ['title'], 'snapshot' => ['_file_hash' => hash('sha256', 'x')]],
    ], 'https://remote.test', 'tok');

    expect($counts)->toBe(['downloaded' => 0, 'skipped' => 0, 'failed' => 0]);
    Http::assertNothingSent();
});

test('uploadChangedFiles posts the local file bytes for a changed file-bearing row', function () {
    $uuid = (string) Str::uuid();
    Storage::disk('local')->put('documents/5/native-path.pdf', 'my-native-bytes');
    Http::fake(['*/api/sync/files/*' => Http::response(['ok' => true], 200)]);

    $counts = $this->service->uploadChangedFiles([
        ['entity_table' => 'document_versions', 'entity_uuid' => $uuid, 'operation' => 'created', 'snapshot' => ['file_path' => 'documents/5/native-path.pdf']],
    ], 'https://remote.test', 'tok');

    expect($counts)->toBe(['uploaded' => 1, 'skipped' => 0, 'failed' => 0]);
    Http::assertSent(fn ($request) => $request->hasFile('file', 'my-native-bytes', 'native-path.pdf'));
});

test('uploadChangedFiles skips when the local file is missing', function () {
    $uuid = (string) Str::uuid();
    Http::fake(['*/api/sync/files/*' => Http::response(['ok' => true], 200)]);

    $counts = $this->service->uploadChangedFiles([
        ['entity_table' => 'document_versions', 'entity_uuid' => $uuid, 'operation' => 'created', 'snapshot' => ['file_path' => 'documents/5/does-not-exist.pdf']],
    ], 'https://remote.test', 'tok');

    expect($counts)->toBe(['uploaded' => 0, 'skipped' => 1, 'failed' => 0]);
    Http::assertNothingSent();
});

test('the file download endpoint requires authentication', function () {
    $this->getJson('/api/sync/files/student_documents/'.Str::uuid())->assertUnauthorized();
});

test('the file download endpoint 404s for a table with no file column', function () {
    Sanctum::actingAs($this->admin, ['sync:read']);

    $this->getJson('/api/sync/files/students/'.Str::uuid())->assertNotFound();
});

test('the file download endpoint streams the exact bytes for a known entity', function () {
    Sanctum::actingAs($this->admin, ['sync:read']);
    $doc = StudentDocument::factory()->create(['file_path' => 'student-documents/1/x.pdf']);
    Storage::disk('local')->put('student-documents/1/x.pdf', 'the-real-content');

    $response = $this->get("/api/sync/files/student_documents/{$doc->uuid}");

    $response->assertOk();
    expect($response->streamedContent())->toBe('the-real-content');
});

test('the file download endpoint 404s when the row or its file does not exist', function () {
    Sanctum::actingAs($this->admin, ['sync:read']);

    $this->getJson('/api/sync/files/student_documents/'.Str::uuid())->assertNotFound();

    $doc = StudentDocument::factory()->create(['file_path' => 'student-documents/1/missing.pdf']);
    $this->getJson("/api/sync/files/student_documents/{$doc->uuid}")->assertNotFound();
});

test('the file upload endpoint requires the row to already exist, then stores bytes at the synced path', function () {
    Sanctum::actingAs($this->admin, ['sync:write']);
    $unknownUuid = (string) Str::uuid();

    $this->postJson("/api/sync/files/student_documents/{$unknownUuid}", [
        'file' => UploadedFile::fake()->createWithContent('x.pdf', 'hello'),
    ])->assertNotFound();

    $doc = StudentDocument::factory()->create();

    $response = $this->postJson("/api/sync/files/student_documents/{$doc->uuid}", [
        'file' => UploadedFile::fake()->createWithContent('x.pdf', 'hello world'),
    ]);

    $response->assertOk();
    Storage::disk('local')->assertExists("synced/student_documents/{$doc->uuid}");
    expect(Storage::disk('local')->get("synced/student_documents/{$doc->uuid}"))->toBe('hello world');
});

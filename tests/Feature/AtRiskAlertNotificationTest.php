<?php

use App\Enums\RoleName;
use App\Models\AcademicDeficiency;
use App\Models\Curriculum;
use App\Models\Department;
use App\Models\ProgressAlert;
use App\Models\Student;
use App\Notifications\AtRiskAlertNotification;
use App\Services\ProgressAlertService;
use Database\Seeders\GradingScaleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(GradingScaleSeeder::class);

    $this->head = userWithRole(RoleName::DepartmentHead->value);
    $this->department = Department::factory()->create(['department_head_id' => $this->head->id]);
    $this->head->update(['department_id' => $this->department->id]);

    $this->adviser = userWithRole(RoleName::Faculty->value, $this->department);
    $this->curriculum = Curriculum::factory()->create();

    $this->student = Student::factory()->create([
        'department_id' => $this->department->id,
        'curriculum_id' => $this->curriculum->id,
        'adviser_id' => $this->adviser->id,
    ]);
});

test('a student with an unresolved deficiency gets their adviser and department head notified', function () {
    Notification::fake();
    AcademicDeficiency::factory()->count(2)->create(['student_id' => $this->student->id]);

    $this->artisan('alerts:at-risk')->assertSuccessful();

    Notification::assertSentTo([$this->adviser, $this->head], AtRiskAlertNotification::class);
    $this->assertDatabaseHas('progress_alerts', [
        'student_id' => $this->student->id,
        'alert_type' => 'multiple_deficiencies',
    ]);
    expect(ProgressAlert::where('student_id', $this->student->id)->first()->notified_at)->not->toBeNull();
});

test('running the command twice does not notify the same open alert episode again', function () {
    Notification::fake();
    AcademicDeficiency::factory()->count(2)->create(['student_id' => $this->student->id]);

    $this->artisan('alerts:at-risk');
    $this->artisan('alerts:at-risk');

    Notification::assertSentToTimes($this->adviser, AtRiskAlertNotification::class, 1);
});

test('a resolved alert that re-triggers is notified again as a fresh episode', function () {
    Notification::fake();
    $deficiencies = AcademicDeficiency::factory()->count(2)->create(['student_id' => $this->student->id]);

    $this->artisan('alerts:at-risk');
    Notification::assertSentToTimes($this->adviser, AtRiskAlertNotification::class, 1);

    // Resolve both deficiencies — the alert should clear on the next sync.
    $deficiencies->each(fn ($d) => $d->update(['resolved_at' => now()]));
    $this->artisan('alerts:at-risk');
    $this->assertDatabaseHas('progress_alerts', ['student_id' => $this->student->id, 'alert_type' => 'multiple_deficiencies']);
    expect(ProgressAlert::where('student_id', $this->student->id)->first()->resolved_at)->not->toBeNull();

    // Re-trigger with fresh deficiencies — should notify again.
    AcademicDeficiency::factory()->count(2)->create(['student_id' => $this->student->id]);
    $this->artisan('alerts:at-risk');

    Notification::assertSentToTimes($this->adviser, AtRiskAlertNotification::class, 2);
});

test('a student with no adviser and no department head still has their alert marked notified', function () {
    Notification::fake();
    $this->head->update(['department_id' => null]);
    $orphanDepartment = Department::factory()->create(['department_head_id' => null]);
    $orphanStudent = Student::factory()->create([
        'department_id' => $orphanDepartment->id,
        'curriculum_id' => $this->curriculum->id,
        'adviser_id' => null,
    ]);
    AcademicDeficiency::factory()->count(2)->create(['student_id' => $orphanStudent->id]);

    $this->artisan('alerts:at-risk')->assertSuccessful();

    expect(ProgressAlert::where('student_id', $orphanStudent->id)->first()->notified_at)->not->toBeNull();
});

test('a student with no unresolved alerts is not notified', function () {
    Notification::fake();

    $this->artisan('alerts:at-risk')->assertSuccessful();

    Notification::assertNothingSent();
});

test('an alert left behind by a since-archived student does not crash the command or get notified', function () {
    Notification::fake();
    AcademicDeficiency::factory()->count(2)->create(['student_id' => $this->student->id]);

    // Sync once to create the alert row, then archive the student —
    // syncAlertsForScope() naturally excludes them from then on (default
    // SoftDeletes scope), leaving their existing alert row orphaned.
    app(ProgressAlertService::class)->syncAlerts($this->student);
    $this->student->delete();

    $this->artisan('alerts:at-risk')->assertSuccessful();

    Notification::assertNothingSent();
});

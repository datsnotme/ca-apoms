<?php

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\FacultyAward;
use App\Models\FacultyCredential;
use App\Models\FacultyEducation;
use App\Models\FacultyTraining;

beforeEach(function () {
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->department = Department::factory()->create();
    $this->head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $this->faculty = userWithRole(RoleName::Faculty->value, $this->department);
});

test('an admin can add, update, and remove an education record', function () {
    $this->actingAs($this->admin)->post("/faculty-profiles/{$this->faculty->id}/education", [
        'level' => 'masters',
        'degree' => 'Master of Science in Agriculture',
        'field_of_study' => 'Soil Science',
        'institution' => 'State University',
        'year_completed' => 2015,
    ])->assertRedirect();

    $this->assertDatabaseHas('faculty_education', [
        'user_id' => $this->faculty->id,
        'degree' => 'Master of Science in Agriculture',
    ]);

    $education = FacultyEducation::where('user_id', $this->faculty->id)->first();

    $this->actingAs($this->admin)->put("/faculty-profiles/{$this->faculty->id}/education/{$education->id}", [
        'level' => 'doctorate',
        'degree' => 'Doctor of Philosophy in Agriculture',
        'institution' => 'State University',
    ])->assertRedirect();

    $this->assertDatabaseHas('faculty_education', ['id' => $education->id, 'level' => 'doctorate']);

    $this->actingAs($this->admin)->delete("/faculty-profiles/{$this->faculty->id}/education/{$education->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('faculty_education', ['id' => $education->id]);
});

test('a faculty member cannot manage their own education records', function () {
    $this->actingAs($this->faculty)->post("/faculty-profiles/{$this->faculty->id}/education", [
        'level' => 'masters',
        'degree' => 'Master of Science',
        'institution' => 'State University',
    ])->assertForbidden();
});

test('a department head cannot manage education records', function () {
    $this->actingAs($this->head)->post("/faculty-profiles/{$this->faculty->id}/education", [
        'level' => 'masters',
        'degree' => 'Master of Science',
        'institution' => 'State University',
    ])->assertForbidden();
});

test('an admin can add a credential with a valid expiry after the issue date', function () {
    $response = $this->actingAs($this->admin)->post("/faculty-profiles/{$this->faculty->id}/credentials", [
        'name' => 'Licensed Agriculturist',
        'issuing_body' => 'PRC',
        'issued_date' => '2020-01-01',
        'expiry_date' => '2025-01-01',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('faculty_credentials', ['user_id' => $this->faculty->id, 'name' => 'Licensed Agriculturist']);
});

test('a credential expiry date before the issue date is rejected', function () {
    $this->actingAs($this->admin)->post("/faculty-profiles/{$this->faculty->id}/credentials", [
        'name' => 'Licensed Agriculturist',
        'issued_date' => '2020-01-01',
        'expiry_date' => '2019-01-01',
    ])->assertSessionHasErrors('expiry_date');
});

test('an admin can add and remove a training', function () {
    $this->actingAs($this->admin)->post("/faculty-profiles/{$this->faculty->id}/trainings", [
        'title' => 'Modern Irrigation Techniques',
        'provider' => 'Department of Agriculture',
        'hours' => 16,
    ])->assertRedirect();

    $training = FacultyTraining::where('user_id', $this->faculty->id)->first();
    expect($training->hours)->toBe(16);

    $this->actingAs($this->admin)->delete("/faculty-profiles/{$this->faculty->id}/trainings/{$training->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('faculty_trainings', ['id' => $training->id]);
});

test('an admin can add an award', function () {
    $this->actingAs($this->admin)->post("/faculty-profiles/{$this->faculty->id}/awards", [
        'title' => 'Outstanding Faculty Award',
        'awarding_body' => 'College of Agriculture',
        'date_awarded' => '2023-06-01',
    ])->assertRedirect();

    $this->assertDatabaseHas('faculty_awards', ['user_id' => $this->faculty->id, 'title' => 'Outstanding Faculty Award']);
});

test('a record cannot be updated or deleted through a mismatched faculty route', function () {
    $otherFaculty = userWithRole(RoleName::Faculty->value, $this->department);
    $credential = FacultyCredential::factory()->create(['user_id' => $this->faculty->id]);

    $this->actingAs($this->admin)->put("/faculty-profiles/{$otherFaculty->id}/credentials/{$credential->id}", [
        'name' => 'Mismatched',
    ])->assertNotFound();

    $this->actingAs($this->admin)->delete("/faculty-profiles/{$otherFaculty->id}/credentials/{$credential->id}")
        ->assertNotFound();
});

test('education, credentials, trainings, and awards all appear on the faculty profile page', function () {
    FacultyEducation::factory()->create(['user_id' => $this->faculty->id]);
    FacultyCredential::factory()->create(['user_id' => $this->faculty->id]);
    FacultyTraining::factory()->create(['user_id' => $this->faculty->id]);
    FacultyAward::factory()->create(['user_id' => $this->faculty->id]);

    $response = $this->actingAs($this->admin)->get("/faculty-profiles/{$this->faculty->id}");

    $response->assertInertia(fn ($page) => $page
        ->has('faculty.education', 1)
        ->has('faculty.credentials', 1)
        ->has('faculty.trainings', 1)
        ->has('faculty.awards', 1)
    );
});

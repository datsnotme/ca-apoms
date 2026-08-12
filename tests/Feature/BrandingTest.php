<?php

use App\Enums\RoleName;
use App\Models\College;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->dean = userWithRole(RoleName::Dean->value);
    $this->college = College::factory()->create();
});

test('an admin can view the branding edit page', function () {
    $this->actingAs($this->admin)->get('/branding')->assertOk();
});

test('a non-admin cannot view or update the branding page', function () {
    $this->actingAs($this->dean)->get('/branding')->assertForbidden();

    $this->actingAs($this->dean)->post('/branding', [
        'logo' => UploadedFile::fake()->image('logo.jpg'),
    ])->assertForbidden();
});

test('an admin can upload a system logo', function () {
    $response = $this->actingAs($this->admin)->post('/branding', [
        'logo' => UploadedFile::fake()->image('logo.jpg'),
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect();

    $this->college->refresh();
    expect($this->college->logo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($this->college->logo_path);
    expect($this->college->logo_url)->toContain($this->college->logo_path);
});

test('uploading a new logo replaces and deletes the previous one', function () {
    $this->actingAs($this->admin)->post('/branding', [
        'logo' => UploadedFile::fake()->image('first.jpg'),
    ]);
    $firstPath = $this->college->refresh()->logo_path;

    $this->actingAs($this->admin)->post('/branding', [
        'logo' => UploadedFile::fake()->image('second.jpg'),
    ]);
    $this->college->refresh();

    expect($this->college->logo_path)->not->toBe($firstPath);
    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($this->college->logo_path);
});

test('a non-image file is rejected', function () {
    $response = $this->actingAs($this->admin)->post('/branding', [
        'logo' => UploadedFile::fake()->create('document.pdf', 100),
    ]);

    $response->assertSessionHasErrors('logo');
});

test('an admin can remove the system logo', function () {
    $this->actingAs($this->admin)->post('/branding', [
        'logo' => UploadedFile::fake()->image('logo.jpg'),
    ]);
    $path = $this->college->refresh()->logo_path;

    $this->actingAs($this->admin)->delete('/branding')->assertRedirect();

    $this->college->refresh();
    expect($this->college->logo_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('the logo url is shared on an authenticated page once uploaded', function () {
    $this->actingAs($this->admin)->post('/branding', [
        'logo' => UploadedFile::fake()->image('logo.jpg'),
    ]);
    $this->college->refresh();

    $response = $this->actingAs($this->admin)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page->where('systemLogoUrl', $this->college->logo_url));
});

test('the logo url is shared on the unauthenticated login page once uploaded', function () {
    $this->actingAs($this->admin)->post('/branding', [
        'logo' => UploadedFile::fake()->image('logo.jpg'),
    ]);
    $this->college->refresh();
    auth()->logout();

    $response = $this->get('/login');

    $response->assertInertia(fn ($page) => $page->where('systemLogoUrl', $this->college->logo_url));
});

test('the shared logo url is null when no logo has been uploaded', function () {
    $response = $this->get('/login');

    $response->assertInertia(fn ($page) => $page->where('systemLogoUrl', null));
});

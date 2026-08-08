<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/profile');

    $response->assertOk();
});

test('a user can update their own name and contact number', function () {
    $user = User::factory()->create(['surname' => 'Original', 'first_name' => 'Name']);

    $response = $this->actingAs($user)->patch('/profile', [
        'surname' => 'Updated',
        'first_name' => 'Name',
        'middle_name' => '',
        'suffix' => '',
        'contact_number' => '0900-111-2222',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect('/profile');

    $user->refresh();
    expect($user->surname)->toBe('Updated');
    expect($user->contact_number)->toBe('0900-111-2222');
});

test('a user cannot self-edit their email, username, or role through the profile form', function () {
    $user = User::factory()->create(['email' => 'original@example.com']);

    $this->actingAs($user)->patch('/profile', [
        'surname' => $user->surname,
        'first_name' => $user->first_name,
        'email' => 'attacker-controlled@example.com',
    ]);

    expect($user->fresh()->email)->toBe('original@example.com');
});

test('a user can upload a profile photo', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/profile/photo', [
        'photo' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect();

    $user->refresh();
    expect($user->profile_photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($user->profile_photo_path);
    expect($user->profile_photo_url)->toContain($user->profile_photo_path);
});

test('uploading a new photo replaces and deletes the previous one', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)->post('/profile/photo', [
        'photo' => UploadedFile::fake()->image('first.jpg'),
    ]);
    $firstPath = $user->refresh()->profile_photo_path;

    $this->actingAs($user)->post('/profile/photo', [
        'photo' => UploadedFile::fake()->image('second.jpg'),
    ]);
    $user->refresh();

    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($user->profile_photo_path);
    expect($user->profile_photo_path)->not->toBe($firstPath);
});

test('a non-image file is rejected', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/profile/photo', [
        'photo' => UploadedFile::fake()->create('resume.pdf', 100),
    ]);

    $response->assertSessionHasErrors('photo');
    expect($user->fresh()->profile_photo_path)->toBeNull();
});

test('a user can remove their profile photo', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $this->actingAs($user)->post('/profile/photo', [
        'photo' => UploadedFile::fake()->image('avatar.jpg'),
    ]);
    $path = $user->refresh()->profile_photo_path;

    $response = $this->actingAs($user)->delete('/profile/photo');

    $response->assertSessionHasNoErrors()->assertRedirect();
    Storage::disk('public')->assertMissing($path);
    expect($user->fresh()->profile_photo_path)->toBeNull();
});

test('the profile photo url is shared globally once uploaded', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $this->actingAs($user)->post('/profile/photo', [
        'photo' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page->where('auth.user.profile_photo_url', $user->fresh()->profile_photo_url));
});

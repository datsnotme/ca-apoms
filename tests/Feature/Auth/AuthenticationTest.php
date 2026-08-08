<?php

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('a successful login is recorded in login_logs and updates last_login_at', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    expect(LoginLog::where('user_id', $user->id)->where('successful', true)->exists())->toBeTrue();
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('a failed login attempt is recorded in login_logs', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);

    expect(LoginLog::where('email_attempted', $user->email)->where('successful', false)->exists())->toBeTrue();
});

test('account locks out after 5 failed attempts', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $_) {
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
    }

    $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();

    RateLimiter::clear(strtolower($user->email).'|127.0.0.1');
});

test('a disabled account cannot log in even with the correct password', function () {
    $user = User::factory()->create(['status' => 'inactive']);

    $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('a user with must_change_password is redirected to the forced password-change screen', function () {
    $user = User::factory()->create(['must_change_password' => true]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect(route('password.force.edit'));
});

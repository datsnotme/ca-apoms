<?php

namespace App\Http\Requests\Auth;

use App\Models\LoginLog;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Account lockout policy: 5 failed attempts locks the email+IP combination
 * for 15 minutes (see ASSUMPTIONS.md). Every attempt, successful or not, is
 * recorded to login_logs for the audit trail.
 */
class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $successful = Auth::attempt($this->only('email', 'password'), $this->boolean('remember'));

        LoginLog::create([
            'user_id' => $successful ? Auth::id() : null,
            'email_attempted' => $this->string('email'),
            'successful' => $successful,
            'ip_address' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'attempted_at' => now(),
        ]);

        if (! $successful) {
            RateLimiter::hit($this->throttleKey(), 900);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        if (! Auth::user()->isActive()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated. Contact the College Administrator.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        Auth::user()->forceFill(['last_login_at' => now()])->save();
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}

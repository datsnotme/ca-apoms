<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable, SoftDeletes;

    protected $appends = [
        'profile_photo_url',
    ];

    protected $fillable = [
        'employee_number',
        'name',
        'surname',
        'first_name',
        'middle_name',
        'suffix',
        'email',
        'username',
        'contact_number',
        'department_id',
        'status',
        'profile_photo_path',
        'password',
        'must_change_password',
        'created_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => ActiveStatus::class,
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'must_change_password' => 'boolean',
        ];
    }

    protected function profilePhotoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->profile_photo_path
                ? Storage::disk('public')->url($this->profile_photo_path)
                : null,
        );
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            // `name` stays in sync with the structured fields so Breeze's
            // existing auth/profile views (which read $user->name) keep
            // working without modification.
            if ($user->first_name || $user->surname) {
                $user->name = trim(implode(' ', array_filter([
                    $user->first_name,
                    $user->middle_name,
                    $user->surname,
                    $user->suffix,
                ])));
            }
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function facultyProfile(): HasOne
    {
        return $this->hasOne(FacultyProfile::class);
    }

    public function education(): HasMany
    {
        return $this->hasMany(FacultyEducation::class);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(FacultyCredential::class);
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(FacultyTraining::class);
    }

    public function awards(): HasMany
    {
        return $this->hasMany(FacultyAward::class);
    }

    public function facultyDocuments(): HasMany
    {
        return $this->hasMany(FacultyDocument::class);
    }

    public function facultyAssignments(): HasMany
    {
        return $this->hasMany(FacultyAssignment::class, 'faculty_id');
    }

    public function isActive(): bool
    {
        return $this->status === ActiveStatus::Active;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'employee_number', 'name', 'email', 'username', 'department_id',
                'status',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('users');
    }
}

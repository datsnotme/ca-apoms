<?php

namespace App\Models;

use App\Enums\RoleName;
use App\Enums\StudentClassification;
use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Student extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'student_number', 'surname', 'first_name', 'middle_name', 'suffix', 'sex',
        'birth_date', 'civil_status', 'citizenship', 'contact_number', 'email',
        'profile_photo_path', 'department_id', 'program_id', 'curriculum_id',
        'year_level_id', 'section_id', 'adviser_id', 'admission_type', 'date_admitted',
        'expected_graduation_date', 'scholarship_status', 'classification', 'status',
        'created_by',
    ];

    protected $appends = ['name'];

    protected function casts(): array
    {
        return [
            'classification' => StudentClassification::class,
            'status' => StudentStatus::class,
            'birth_date' => 'date',
            'date_admitted' => 'date',
            'expected_graduation_date' => 'date',
            'sync_version' => 'integer',
        ];
    }

    /**
     * Transient (not persisted on the model itself) reason for the status
     * change about to be saved. Set by the controller before update() so the
     * auto-written student_status_histories row can capture it.
     */
    public ?string $statusChangeReason = null;

    protected static function booted(): void
    {
        static::updating(function (Student $student) {
            if ($student->isDirty('status')) {
                $student->statusHistories()->create([
                    'from_status' => $student->getOriginal('status'),
                    'to_status' => $student->status,
                    'reason' => $student->statusChangeReason,
                    'changed_by' => auth()->id(),
                    'effective_date' => now()->toDateString(),
                ]);
            }

            if ($student->isDirty('adviser_id')) {
                $student->closeActiveAdviserRow();

                if ($student->adviser_id) {
                    $student->advisers()->create([
                        'faculty_id' => $student->adviser_id,
                        'assigned_by' => auth()->id(),
                        'assigned_at' => now(),
                    ]);
                }
            }
        });

        static::created(function (Student $student) {
            $student->statusHistories()->create([
                'from_status' => null,
                'to_status' => $student->status,
                'changed_by' => auth()->id(),
                'effective_date' => now()->toDateString(),
            ]);

            if ($student->adviser_id) {
                $student->advisers()->create([
                    'faculty_id' => $student->adviser_id,
                    'assigned_by' => auth()->id(),
                    'assigned_at' => now(),
                ]);
            }
        });
    }

    /**
     * Closes the currently-active (unassigned_at IS NULL) student_advisers
     * row, if any — called before either assigning a new adviser or
     * unassigning entirely.
     */
    public function closeActiveAdviserRow(): void
    {
        $this->advisers()->whereNull('unassigned_at')->update(['unassigned_at' => now()]);
    }

    public function getNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name, $this->middle_name, $this->surname, $this->suffix,
        ])));
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function yearLevel(): BelongsTo
    {
        return $this->belongsTo(YearLevel::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(StudentGuardian::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(StudentAddress::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(StudentStatusHistory::class)->latest('effective_date');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function deficiencies(): HasMany
    {
        return $this->hasMany(AcademicDeficiency::class);
    }

    public function advisers(): HasMany
    {
        return $this->hasMany(StudentAdviser::class)->latest('assigned_at');
    }

    public function advisingRecords(): HasMany
    {
        return $this->hasMany(StudentAdvisingRecord::class)->latest('session_date');
    }

    public function latestAdvisingRecord(): HasOne
    {
        return $this->hasOne(StudentAdvisingRecord::class)->latestOfMany('session_date');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(ProgressAlert::class);
    }

    public function interventionFollowups(): HasMany
    {
        return $this->hasMany(StudentInterventionFollowup::class)->latest('due_date');
    }

    public function graduationCandidacies(): HasMany
    {
        return $this->hasMany(GraduationCandidate::class)->latest('nominated_at');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])) {
            return $query;
        }

        return $query->where('department_id', $user->department_id);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'student_number', 'surname', 'first_name', 'department_id', 'program_id',
                'curriculum_id', 'year_level_id', 'section_id', 'classification', 'status',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('students');
    }
}

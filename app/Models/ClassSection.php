<?php

namespace App\Models;

use App\Enums\ClassSectionStatus;
use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ClassSection extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = ['course_id', 'semester_id', 'section_label', 'max_students', 'status'];

    protected function casts(): array
    {
        return ['status' => ClassSectionStatus::class];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function facultyAssignments(): HasMany
    {
        return $this->hasMany(FacultyAssignment::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public function enrollmentCourses(): HasMany
    {
        return $this->hasMany(EnrollmentCourse::class);
    }

    public function gradeSubmission(): HasOne
    {
        return $this->hasOne(GradeSubmission::class);
    }

    public function enrolledCount(): int
    {
        return $this->enrollmentCourses()
            ->whereIn('status', ['Enrolled', 'Added', 'Repeated'])
            ->count();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])) {
            return $query;
        }

        return $query->whereHas('course', fn ($q) => $q->where('department_id', $user->department_id));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

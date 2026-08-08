<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Curriculum extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'program_id', 'effective_academic_year_id', 'code', 'name',
        'required_total_units', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['status' => ActiveStatus::class];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function effectiveAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'effective_academic_year_id');
    }

    public function curriculumCourses(): HasMany
    {
        return $this->hasMany(CurriculumCourse::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function totalUnits(): float
    {
        return (float) $this->curriculumCourses()->sum('units');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])) {
            return $query;
        }

        return $query->whereHas(
            'program',
            fn (Builder $q) => $q->where('department_id', $user->department_id)
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

<?php

namespace App\Models;

use App\Enums\CourseCategory;
use App\Enums\RoleName;
use App\Enums\SemesterTerm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Course extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'department_id', 'code', 'title', 'description', 'units', 'lecture_hours',
        'laboratory_hours', 'category', 'recommended_year_level', 'recommended_semester',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'category' => CourseCategory::class,
            'recommended_semester' => SemesterTerm::class,
            'is_active' => 'boolean',
            'units' => 'decimal:2',
            'lecture_hours' => 'decimal:2',
            'laboratory_hours' => 'decimal:2',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function curriculumCourses(): HasMany
    {
        return $this->hasMany(CurriculumCourse::class);
    }

    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(
            Course::class,
            'course_prerequisites',
            'course_id',
            'prerequisite_course_id'
        );
    }

    public function corequisites(): BelongsToMany
    {
        return $this->belongsToMany(
            Course::class,
            'course_corequisites',
            'course_id',
            'corequisite_course_id'
        );
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
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

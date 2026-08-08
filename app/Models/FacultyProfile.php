<?php

namespace App\Models;

use App\Enums\FacultyEmploymentStatus;
use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FacultyProfile extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id', 'academic_rank', 'employment_status', 'specialization',
        'office_location', 'date_hired', 'bio',
    ];

    protected function casts(): array
    {
        return [
            'employment_status' => FacultyEmploymentStatus::class,
            'date_hired' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisibleTo(Builder $query, User $viewer): Builder
    {
        if ($viewer->hasRole([RoleName::Administrator->value, RoleName::Dean->value])) {
            return $query;
        }

        if ($viewer->hasRole(RoleName::DepartmentHead->value)) {
            return $query->whereHas('user', fn ($q) => $q->where('department_id', $viewer->department_id));
        }

        return $query->where('user_id', $viewer->id);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

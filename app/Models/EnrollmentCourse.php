<?php

namespace App\Models;

use App\Enums\EnrollmentCourseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EnrollmentCourse extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = ['student_enrollment_id', 'class_section_id', 'status'];

    protected function casts(): array
    {
        return ['status' => EnrollmentCourseStatus::class];
    }

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function studentGrade(): HasOne
    {
        return $this->hasOne(StudentGrade::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

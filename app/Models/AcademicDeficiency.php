<?php

namespace App\Models;

use App\Enums\DeficiencyResolution;
use App\Enums\DeficiencyType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AcademicDeficiency extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'student_id', 'curriculum_course_id', 'deficiency_type',
        'detected_at', 'resolved_at', 'resolved_via', 'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'deficiency_type' => DeficiencyType::class,
            'resolved_via' => DeficiencyResolution::class,
            'detected_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function curriculumCourse(): BelongsTo
    {
        return $this->belongsTo(CurriculumCourse::class);
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

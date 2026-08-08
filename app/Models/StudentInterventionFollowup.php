<?php

namespace App\Models;

use App\Enums\InterventionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StudentInterventionFollowup extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'student_id', 'student_advising_record_id', 'progress_alert_id', 'description',
        'assigned_to', 'due_date', 'status', 'completed_by', 'completed_at', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => InterventionStatus::class,
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function advisingRecord(): BelongsTo
    {
        return $this->belongsTo(StudentAdvisingRecord::class, 'student_advising_record_id');
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(ProgressAlert::class, 'progress_alert_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

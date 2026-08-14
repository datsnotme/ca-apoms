<?php

namespace App\Models;

use App\Enums\DocumentCategory;
use App\Enums\DocumentVerificationStatus;
use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StudentDocument extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'student_id', 'category', 'title', 'file_path', 'original_filename',
        'file_type', 'file_size', 'uploaded_by', 'uploaded_at',
        'verification_status', 'verified_by', 'verified_at', 'remarks', 'visibility_level',
    ];

    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'verification_status' => DocumentVerificationStatus::class,
            'uploaded_at' => 'datetime',
            'verified_at' => 'datetime',
            'sync_version' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])) {
            return $query;
        }

        return $query->whereHas('student', fn ($q) => $q->where('department_id', $user->department_id));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['student_id', 'category', 'title', 'verification_status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('student-documents');
    }
}

<?php

namespace App\Models;

use App\Enums\DocumentVerificationStatus;
use App\Enums\FacultyDocumentCategory;
use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FacultyDocument extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'user_id', 'category', 'title', 'file_path', 'original_filename',
        'file_type', 'file_size', 'uploaded_by', 'uploaded_at',
        'verification_status', 'verified_by', 'verified_at', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'category' => FacultyDocumentCategory::class,
            'verification_status' => DocumentVerificationStatus::class,
            'uploaded_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

        if ($user->hasRole(RoleName::DepartmentHead->value)) {
            return $query->whereHas('user', fn ($q) => $q->where('department_id', $user->department_id));
        }

        return $query->where('user_id', $user->id);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id', 'category', 'title', 'verification_status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('faculty-documents');
    }
}

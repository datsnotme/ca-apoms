<?php

namespace App\Models;

use App\Enums\InternalRequestStatus;
use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InternalRequest extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'requester_id', 'department_id', 'type', 'title', 'description',
        'status', 'reviewed_by', 'reviewed_at', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'status' => InternalRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(RequestHistory::class)->latest('created_at');
    }

    /**
     * Every status transition is recorded automatically — the caller only
     * ever sets `status` (plus `remarks` where relevant) and this writes the
     * audit trail row, the same model-event pattern Student (Phase 2C) uses
     * for student_status_histories. See ASSUMPTIONS.md.
     */
    protected static function booted(): void
    {
        static::created(function (InternalRequest $request) {
            $request->histories()->create([
                'from_status' => null,
                'to_status' => $request->status->value,
                'changed_by' => auth()->id(),
            ]);
        });

        static::updating(function (InternalRequest $request) {
            if ($request->isDirty('status')) {
                $request->histories()->create([
                    'from_status' => $request->getOriginal('status')?->value,
                    'to_status' => $request->status->value,
                    'reason' => $request->remarks,
                    'changed_by' => auth()->id(),
                ]);
            }
        });
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])) {
            return $query;
        }

        if ($user->hasRole(RoleName::DepartmentHead->value)) {
            return $query->where('department_id', $user->department_id);
        }

        return $query->where('requester_id', $user->id);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

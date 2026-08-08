<?php

namespace App\Models;

use App\Enums\ActionItemStatus;
use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Task extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'title', 'description', 'assigned_to', 'due_date',
        'status', 'completed_by', 'completed_at', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ActionItemStatus::class,
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
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

    /**
     * A Task has no department scope — it's a personal delegation between a
     * creator and an assignee, not operational broadcast content. Admin
     * sees every task (oversight); everyone else sees only tasks they
     * created or were assigned. See ASSUMPTIONS.md.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole(RoleName::Administrator->value)) {
            return $query;
        }

        return $query->where(fn ($q) => $q->where('created_by', $user->id)->orWhere('assigned_to', $user->id));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

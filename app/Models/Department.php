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

class Department extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'college_id', 'code', 'name', 'description', 'department_head_id',
        'office_location', 'contact_info', 'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ActiveStatus::class,
            'sync_version' => 'integer',
        ];
    }

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'department_head_id');
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Department-aware access scope: Admin and Dean see every department;
     * a Department Head or Faculty Member sees only their own.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])) {
            return $query;
        }

        return $query->where('id', $user->department_id);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('departments');
    }
}

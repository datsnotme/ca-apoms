<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Program extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'department_id', 'code', 'name', 'degree_type', 'major',
        'required_total_units', 'duration_years', 'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ActiveStatus::class,
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])) {
            return $query;
        }

        return $query->whereHas(
            'department',
            fn (Builder $q) => $q->where('id', $user->department_id)
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('programs');
    }
}

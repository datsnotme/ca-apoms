<?php

namespace App\Models;

use App\Enums\ResearchProjectStatus;
use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ResearchProject extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'department_id', 'title', 'description', 'status',
        'start_date', 'end_date', 'funding_source', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ResearchProjectStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ResearchMember::class);
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(ResearchOutput::class);
    }

    public function isLedBy(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->where('is_lead', true)->exists();
    }

    /**
     * Unlike Meeting/Announcement/Event (nullable department_id, meaning
     * "entire college"), a research project always belongs to exactly one
     * department — there's no college-wide broadcast case here.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])) {
            return $query;
        }

        return $query->where('department_id', $user->department_id);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

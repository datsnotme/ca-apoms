<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CompetencyEvaluator extends Model
{
    use LogsActivity;

    protected $fillable = ['graduation_candidate_id', 'evaluator_id', 'assigned_by', 'assigned_at'];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime'];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(GraduationCandidate::class, 'graduation_candidate_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(CompetencyRating::class);
    }

    /**
     * This evaluator has rated every currently-defined competency indicator.
     */
    public function isComplete(): bool
    {
        $indicatorCount = CompetencyIndicator::query()->count();

        return $indicatorCount > 0 && $this->ratings()->count() >= $indicatorCount;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CompetencyRating extends Model
{
    use LogsActivity;

    protected $fillable = ['competency_evaluator_id', 'competency_indicator_id', 'rating', 'remarks', 'rated_at'];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'rated_at' => 'datetime'];
    }

    public function evaluatorAssignment(): BelongsTo
    {
        return $this->belongsTo(CompetencyEvaluator::class, 'competency_evaluator_id');
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(CompetencyIndicator::class, 'competency_indicator_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

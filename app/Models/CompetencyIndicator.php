<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CompetencyIndicator extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = ['competency_category_id', 'title', 'description', 'sort_order'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompetencyCategory::class, 'competency_category_id');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(CompetencyRating::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

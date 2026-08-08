<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GraduationRequirementTemplate extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = ['program_id', 'title', 'description', 'is_required', 'sort_order'];

    protected function casts(): array
    {
        return ['is_required' => 'boolean'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function studentRequirements(): HasMany
    {
        return $this->hasMany(StudentGraduationRequirement::class, 'requirement_template_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

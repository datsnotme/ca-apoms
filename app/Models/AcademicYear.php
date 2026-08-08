<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AcademicYear extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = ['start_year', 'end_year', 'is_current'];

    protected function casts(): array
    {
        return ['is_current' => 'boolean'];
    }

    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class);
    }

    public function getLabelAttribute(): string
    {
        return "{$this->start_year}-{$this->end_year}";
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

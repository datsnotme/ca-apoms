<?php

namespace App\Models;

use App\Enums\SemesterTerm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Semester extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = ['academic_year_id', 'term', 'start_date', 'end_date', 'is_current'];

    protected function casts(): array
    {
        return [
            'term' => SemesterTerm::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    protected static function booted(): void
    {
        // Only one semester system-wide may be "current" at a time.
        static::saved(function (Semester $semester) {
            if ($semester->is_current) {
                static::where('id', '!=', $semester->id)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

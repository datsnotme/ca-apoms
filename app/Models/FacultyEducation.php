<?php

namespace App\Models;

use App\Enums\FacultyEducationLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FacultyEducation extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['user_id', 'level', 'degree', 'field_of_study', 'institution', 'year_completed'];

    protected function casts(): array
    {
        return ['level' => FacultyEducationLevel::class];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

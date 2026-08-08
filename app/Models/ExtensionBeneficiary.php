<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ExtensionBeneficiary extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'extension_project_id', 'beneficiary_name', 'beneficiary_type',
        'count', 'location', 'notes', 'created_by',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ExtensionProject::class, 'extension_project_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

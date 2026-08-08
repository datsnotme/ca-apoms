<?php

namespace App\Models;

use App\Enums\EquipmentStatus;
use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Equipment extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name', 'type', 'department_id', 'facility_id', 'serial_number', 'status', 'description', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => EquipmentStatus::class,
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function borrowings(): HasMany
    {
        return $this->hasMany(EquipmentBorrowing::class);
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(EquipmentMaintenance::class);
    }

    /**
     * The single outstanding (unreturned) borrowing, if any — same
     * "latest/current row via a relation" shape as Document::latestVersion()
     * (Phase 6E), computed via a relation rather than a persisted pointer.
     */
    public function activeBorrowing(): HasOne
    {
        return $this->hasOne(EquipmentBorrowing::class)->whereDoesntHave('return')->latestOfMany();
    }

    /**
     * Same nullable-department shape as Facility (Phase 7C): null means
     * shared/college-wide, set means department-owned.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])) {
            return $query;
        }

        return $query->where(fn ($q) => $q->whereNull('department_id')->orWhere('department_id', $user->department_id));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

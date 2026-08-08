<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EquipmentBorrowing extends Model
{
    use HasFactory;

    protected $fillable = ['equipment_id', 'borrowed_by', 'borrowed_at', 'expected_return_at', 'purpose', 'recorded_by'];

    protected function casts(): array
    {
        return [
            'borrowed_at' => 'datetime',
            'expected_return_at' => 'date',
        ];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function borrowedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'borrowed_by');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function return(): HasOne
    {
        return $this->hasOne(EquipmentReturn::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentReturn extends Model
{
    use HasFactory;

    protected $fillable = ['equipment_borrowing_id', 'returned_at', 'condition_on_return', 'notes', 'recorded_by'];

    protected function casts(): array
    {
        return [
            'returned_at' => 'datetime',
        ];
    }

    public function borrowing(): BelongsTo
    {
        return $this->belongsTo(EquipmentBorrowing::class, 'equipment_borrowing_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

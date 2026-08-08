<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentMaintenance extends Model
{
    use HasFactory;

    // Eloquent's default pluralization guesses "equipment_maintenances";
    // the migration uses the singular "equipment_maintenance" to match the
    // spec's literal table name (DATABASE_DESIGN.md).
    protected $table = 'equipment_maintenance';

    protected $fillable = ['equipment_id', 'description', 'started_at', 'completed_at', 'performed_by', 'notes', 'recorded_by'];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'completed_at' => 'date',
        ];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

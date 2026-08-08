<?php

namespace App\Models;

use App\Enums\ImportStatus;
use App\Enums\ImportType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'file_name', 'uploaded_by', 'status', 'total_rows', 'success_rows', 'error_rows'];

    protected function casts(): array
    {
        return [
            'type' => ImportType::class,
            'status' => ImportStatus::class,
        ];
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function errors(): HasMany
    {
        return $this->hasMany(ImportBatchError::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatchError extends Model
{
    protected $fillable = ['import_batch_id', 'row_number', 'raw_data', 'error_message'];

    protected function casts(): array
    {
        return ['raw_data' => 'array'];
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }
}

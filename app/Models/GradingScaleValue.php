<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradingScaleValue extends Model
{
    protected $fillable = [
        'grading_scale_id', 'value', 'label', 'numeric_equivalent',
        'is_passing', 'is_failing', 'is_special', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'numeric_equivalent' => 'decimal:2',
            'is_passing' => 'boolean',
            'is_failing' => 'boolean',
            'is_special' => 'boolean',
        ];
    }

    public function gradingScale(): BelongsTo
    {
        return $this->belongsTo(GradingScale::class);
    }
}

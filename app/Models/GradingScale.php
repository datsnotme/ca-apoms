<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradingScale extends Model
{
    protected $fillable = ['name', 'is_default', 'description'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function values(): HasMany
    {
        return $this->hasMany(GradingScaleValue::class)->orderBy('sort_order');
    }

    public static function default(): self
    {
        return static::where('is_default', true)->firstOrFail();
    }
}

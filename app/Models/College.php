<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class College extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = [
        'logo_url',
    ];

    protected $fillable = [
        'code', 'name', 'address', 'contact_email', 'contact_phone', 'logo_path',
    ];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->logo_path
                ? Storage::disk('public')->url($this->logo_path)
                : null,
        );
    }
}

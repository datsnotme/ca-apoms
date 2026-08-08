<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtensionMember extends Model
{
    use HasFactory;

    protected $fillable = ['extension_project_id', 'user_id', 'is_lead', 'added_by'];

    protected function casts(): array
    {
        return [
            'is_lead' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ExtensionProject::class, 'extension_project_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}

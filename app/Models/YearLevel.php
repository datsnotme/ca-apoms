<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class YearLevel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['level', 'label'];

    protected function casts(): array
    {
        return ['sync_version' => 'integer'];
    }
}

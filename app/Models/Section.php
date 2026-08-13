<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Section extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['program_id', 'year_level_id', 'academic_year_id', 'name', 'capacity'];

    protected function casts(): array
    {
        return ['sync_version' => 'integer'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function yearLevel(): BelongsTo
    {
        return $this->belongsTo(YearLevel::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}

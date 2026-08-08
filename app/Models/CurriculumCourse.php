<?php

namespace App\Models;

use App\Enums\SemesterTerm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumCourse extends Model
{
    use HasFactory;

    protected $fillable = [
        'curriculum_id', 'course_id', 'year_level', 'semester', 'is_required', 'units', 'sequence_order',
    ];

    protected function casts(): array
    {
        return [
            'semester' => SemesterTerm::class,
            'is_required' => 'boolean',
            'units' => 'decimal:2',
        ];
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}

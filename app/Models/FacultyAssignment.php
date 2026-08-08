<?php

namespace App\Models;

use App\Enums\FacultyAssignmentRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacultyAssignment extends Model
{
    protected $fillable = ['class_section_id', 'faculty_id', 'role'];

    protected function casts(): array
    {
        return ['role' => FacultyAssignmentRole::class];
    }

    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }
}

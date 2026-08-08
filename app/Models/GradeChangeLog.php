<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeChangeLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['student_grade_id', 'previous_grade', 'new_grade', 'changed_by', 'reason', 'approved_by'];

    public function studentGrade(): BelongsTo
    {
        return $this->belongsTo(StudentGrade::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

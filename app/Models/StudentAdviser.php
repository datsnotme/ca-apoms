<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An auto-generated history log of adviser assignments — written by a
 * Student model event whenever `adviser_id` changes, mirroring how
 * student_status_histories tracks status changes. `students.adviser_id`
 * remains the single editable field; this table is read-only from the UI.
 */
class StudentAdviser extends Model
{
    protected $fillable = ['student_id', 'faculty_id', 'assigned_by', 'assigned_at', 'unassigned_at'];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'unassigned_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}

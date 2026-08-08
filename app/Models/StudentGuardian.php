<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentGuardian extends Model
{
    protected $fillable = ['student_id', 'type', 'name', 'relationship', 'contact_number', 'address'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}

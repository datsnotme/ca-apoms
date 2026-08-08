<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAddress extends Model
{
    protected $fillable = ['student_id', 'type', 'address_line'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}

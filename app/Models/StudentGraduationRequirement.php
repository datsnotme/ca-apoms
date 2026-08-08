<?php

namespace App\Models;

use App\Enums\GraduationRequirementStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentGraduationRequirement extends Model
{
    protected $fillable = ['graduation_candidate_id', 'requirement_template_id', 'status', 'satisfied_by', 'satisfied_at', 'remarks'];

    protected function casts(): array
    {
        return [
            'status' => GraduationRequirementStatus::class,
            'satisfied_at' => 'datetime',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(GraduationCandidate::class, 'graduation_candidate_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(GraduationRequirementTemplate::class, 'requirement_template_id');
    }

    public function satisfiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'satisfied_by');
    }
}

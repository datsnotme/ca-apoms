<?php

namespace App\Models;

use App\Enums\StudentGradeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentGrade extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['enrollment_course_id', 'grade', 'status', 'encoded_by'];

    /**
     * Transient context for the change-log entry auto-written on grade-value
     * changes (see booted()). Set by GradeService before save().
     */
    public ?string $changeReason = null;

    public ?int $changeApprovedBy = null;

    /**
     * Captured in the `saving` hook, before Eloquent clears dirty-attribute
     * tracking. `wasChanged()` is only reliable after an update — on a fresh
     * insert Eloquent never populates it — so pre-save `isDirty()`/
     * `getOriginal()` is the only way to detect a grade change on both paths.
     */
    private ?array $pendingGradeChange = null;

    protected function casts(): array
    {
        return ['status' => StudentGradeStatus::class];
    }

    protected static function booted(): void
    {
        static::saving(function (StudentGrade $studentGrade) {
            $studentGrade->pendingGradeChange = $studentGrade->isDirty('grade')
                ? ['from' => $studentGrade->getOriginal('grade'), 'to' => $studentGrade->grade]
                : null;
        });

        static::saved(function (StudentGrade $studentGrade) {
            if (! $studentGrade->pendingGradeChange) {
                return;
            }

            $studentGrade->changeLogs()->create([
                'previous_grade' => $studentGrade->pendingGradeChange['from'],
                'new_grade' => $studentGrade->pendingGradeChange['to'],
                'changed_by' => $studentGrade->encoded_by ?? auth()->id(),
                'reason' => $studentGrade->changeReason,
                'approved_by' => $studentGrade->changeApprovedBy,
            ]);

            $studentGrade->pendingGradeChange = null;
        });
    }

    public function enrollmentCourse(): BelongsTo
    {
        return $this->belongsTo(EnrollmentCourse::class);
    }

    public function encodedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'encoded_by');
    }

    public function changeLogs(): HasMany
    {
        return $this->hasMany(GradeChangeLog::class)->latest('created_at');
    }
}

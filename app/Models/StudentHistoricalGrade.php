<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A prior-program grade record transcribed from a paper/PDF academic record
 * (e.g. a shiftee or transferee's coursework under a program the student is
 * no longer in) and imported for one student at a time via a spreadsheet
 * upload. Deliberately not tied to a live CurriculumCourse/ClassSection/
 * EnrollmentCourse — it exists only to display on the printed Student
 * Evaluation and never feeds GWA, completion percentage, or progress
 * tracking elsewhere in the app. See ASSUMPTIONS.md.
 */
class StudentHistoricalGrade extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'student_id', 'academic_year_label', 'semester_label', 'program_label',
        'course_code', 'course_title', 'lecture_hours', 'laboratory_hours', 'units', 'grade',
        'imported_by',
    ];

    protected function casts(): array
    {
        return [
            'lecture_hours' => 'decimal:2',
            'laboratory_hours' => 'decimal:2',
            'units' => 'decimal:2',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

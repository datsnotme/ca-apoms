<?php

namespace App\Models;

use App\Enums\GraduationCandidateStatus;
use App\Enums\GraduationRequirementStatus;
use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GraduationCandidate extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'student_id', 'academic_year_id', 'semester_id', 'status',
        'gwa_snapshot', 'completion_percentage_snapshot', 'deficiency_count_snapshot',
        'nominated_by', 'nominated_at', 'created_by',
        'recommended_by', 'recommended_at', 'recommendation_remarks',
        'decided_by', 'decided_at', 'decision_remarks',
        'graduated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => GraduationCandidateStatus::class,
            'gwa_snapshot' => 'decimal:2',
            'completion_percentage_snapshot' => 'decimal:2',
            'nominated_at' => 'datetime',
            'recommended_at' => 'datetime',
            'decided_at' => 'datetime',
            'graduated_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function nominatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nominated_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recommendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recommended_by');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(StudentGraduationRequirement::class);
    }

    public function competencyEvaluators(): HasMany
    {
        return $this->hasMany(CompetencyEvaluator::class);
    }

    /**
     * All requirements are satisfied or explicitly waived — none left pending.
     */
    public function requirementsComplete(): bool
    {
        return ! $this->requirements()->where('status', GraduationRequirementStatus::Pending->value)->exists();
    }

    /**
     * At least one evaluator is assigned, and every assigned evaluator has rated every
     * currently-defined competency indicator.
     */
    public function evaluationComplete(): bool
    {
        $evaluators = $this->competencyEvaluators()->withCount('ratings')->get();

        if ($evaluators->isEmpty()) {
            return false;
        }

        $indicatorCount = CompetencyIndicator::query()->count();

        if ($indicatorCount === 0) {
            return false;
        }

        return $evaluators->every(fn (CompetencyEvaluator $e) => $e->ratings_count >= $indicatorCount);
    }

    /**
     * Both the requirement checklist and the competency evaluation are done —
     * the gate a Department Head must clear before recommending a candidate.
     */
    public function readyForRecommendation(): bool
    {
        return $this->requirementsComplete() && $this->evaluationComplete();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])) {
            return $query;
        }

        if ($user->hasRole(RoleName::DepartmentHead->value)) {
            return $query->whereHas('student', fn ($q) => $q->where('department_id', $user->department_id));
        }

        return $query->whereHas('competencyEvaluators', fn ($q) => $q->where('evaluator_id', $user->id));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

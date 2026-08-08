<?php

namespace App\Http\Controllers\Progress;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentAdvisingRecord;
use App\Models\StudentInterventionFollowup;
use App\Models\User;
use App\Services\ProgressAlertService;
use App\Services\ProgressComputationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentProgressController extends Controller
{
    public function __construct(
        private readonly ProgressComputationService $progress,
        private readonly ProgressAlertService $alerts,
    ) {}

    public function show(Request $request, Student $student): Response
    {
        $this->authorize('viewProgress', $student);

        // Kept in sync on every view rather than on a schedule — see
        // ASSUMPTIONS.md. Cheap at this data scale and guarantees whoever is
        // looking at deficiencies never sees a stale list.
        $this->progress->syncDeficiencies($student);
        $this->alerts->syncAlerts($student);

        $student->load([
            'department:id,name', 'program:id,name', 'curriculum:id,name', 'yearLevel:id,level,label', 'adviser:id,name',
            'advisingRecords.adviser:id,name', 'advisingRecords.semester:id,academic_year_id,term', 'advisingRecords.semester.academicYear:id,start_year,end_year',
            'interventionFollowups.assignedTo:id,name', 'interventionFollowups.completedBy:id,name',
        ]);

        return Inertia::render('Progress/Show', [
            'student' => [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'name' => $student->name,
                'department' => $student->department,
                'program' => $student->program,
                'curriculum' => $student->curriculum,
                'year_level' => $student->yearLevel,
                'adviser' => $student->adviser,
            ],
            'checklist' => $this->progress->checklist($student)->values(),
            'gwa' => $this->progress->gwa($student),
            'completionPercentage' => $this->progress->completionPercentage($student),
            'deficiencies' => $student->deficiencies()
                ->whereNull('resolved_at')
                ->with('curriculumCourse.course:id,code,title')
                ->get(),
            'alerts' => $student->alerts()->whereNull('resolved_at')->orderByDesc('severity')->get(),
            'advisingRecords' => $student->advisingRecords,
            'semesters' => Semester::with('academicYear:id,start_year,end_year')
                ->orderByDesc('id')
                ->get(['id', 'academic_year_id', 'term'])
                ->map(fn ($s) => ['id' => $s->id, 'label' => "{$s->academicYear->start_year}-{$s->academicYear->end_year} {$s->term->label()}"]),
            'canManageAdvising' => $request->user()->can('create', [StudentAdvisingRecord::class, $student]),
            'interventionFollowups' => $student->interventionFollowups,
            'canManageFollowups' => $request->user()->can('create', [StudentInterventionFollowup::class, $student]),
            'advisers' => User::role([RoleName::Faculty->value, RoleName::DepartmentHead->value])
                ->orderBy('surname')->get(['id', 'name']),
        ]);
    }
}

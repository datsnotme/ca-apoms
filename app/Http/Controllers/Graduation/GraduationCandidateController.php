<?php

namespace App\Http\Controllers\Graduation;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\CompetencyCategory;
use App\Models\GraduationCandidate;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\GraduationCandidateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GraduationCandidateController extends Controller
{
    public function __construct(private readonly GraduationCandidateService $candidates) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', GraduationCandidate::class);

        $candidates = GraduationCandidate::query()
            ->visibleTo($request->user())
            ->with(['student:id,student_number,surname,first_name,middle_name,department_id,program_id', 'student.department:id,name', 'student.program:id,name', 'academicYear:id,start_year,end_year', 'semester:id,term'])
            ->when($request->string('status')->trim()->isNotEmpty(), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('nominated_at')
            ->paginate(20)
            ->withQueryString();

        $canManage = $request->user()->can('create', GraduationCandidate::class);

        return Inertia::render('GraduationCandidates/Index', [
            'candidates' => $candidates,
            'filters' => $request->only('status'),
            'canManage' => $canManage,
            'academicYears' => AcademicYear::query()->orderByDesc('start_year')->get(['id', 'start_year', 'end_year']),
            'semesters' => Semester::with('academicYear:id,start_year,end_year')
                ->orderByDesc('id')
                ->get(['id', 'academic_year_id', 'term'])
                ->map(fn ($s) => ['id' => $s->id, 'label' => "{$s->academicYear->start_year}-{$s->academicYear->end_year} {$s->term->label()}"]),
            ...($canManage ? [
                'eligibleStudents' => $this->candidates->identifyEligibleStudents()
                    ->map(fn ($s) => ['id' => $s->id, 'student_number' => $s->student_number, 'name' => $s->name]),
            ] : []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', GraduationCandidate::class);

        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
        ]);

        $student = Student::findOrFail($data['student_id']);
        $academicYear = AcademicYear::findOrFail($data['academic_year_id']);
        $semester = Semester::findOrFail($data['semester_id']);

        $candidate = $this->candidates->nominate($student, $academicYear, $semester, $request->user());

        return redirect()->route('graduation-candidates.show', $candidate)->with('success', 'Student nominated as a graduation candidate.');
    }

    public function show(Request $request, GraduationCandidate $graduationCandidate): Response
    {
        $this->authorize('view', $graduationCandidate);

        $graduationCandidate->load([
            'student:id,student_number,surname,first_name,middle_name,department_id,program_id,adviser_id',
            'student.department:id,name', 'student.program:id,name', 'student.adviser:id,name',
            'academicYear:id,start_year,end_year', 'semester:id,term',
            'nominatedBy:id,name', 'recommendedBy:id,name', 'decidedBy:id,name',
            'requirements.template', 'requirements.satisfiedBy:id,name',
            'competencyEvaluators.evaluator:id,name',
            'competencyEvaluators.ratings',
        ]);

        $user = $request->user();
        $canManage = $user->can('update', $graduationCandidate);
        $myAssignment = $graduationCandidate->competencyEvaluators->firstWhere('evaluator_id', $user->id);

        return Inertia::render('GraduationCandidates/Show', [
            'candidate' => $graduationCandidate,
            'canManage' => $canManage,
            'evaluationComplete' => $graduationCandidate->evaluationComplete(),
            'readyForRecommendation' => $graduationCandidate->readyForRecommendation(),
            'canRecommend' => $user->can('recommend', $graduationCandidate),
            'canDecide' => $user->can('decide', $graduationCandidate),
            'canConfer' => $canManage,
            'competencyCategories' => CompetencyCategory::query()
                ->with(['indicators' => fn ($q) => $q->orderBy('sort_order')->orderBy('title')])
                ->orderBy('sort_order')->orderBy('name')
                ->get(),
            'availableEvaluators' => $canManage
                ? User::role(RoleName::Faculty->value)
                    ->where('department_id', $graduationCandidate->student->department_id)
                    ->whereNotIn('id', $graduationCandidate->competencyEvaluators->pluck('evaluator_id'))
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : [],
            'myAssignmentId' => $myAssignment?->id,
            'myRatings' => $myAssignment
                ? $myAssignment->ratings->keyBy('competency_indicator_id')->map(fn ($r) => ['rating' => $r->rating, 'remarks' => $r->remarks])
                : [],
        ]);
    }

    public function destroy(GraduationCandidate $graduationCandidate): RedirectResponse
    {
        $this->authorize('delete', $graduationCandidate);

        $graduationCandidate->delete();

        return redirect()->route('graduation-candidates.index')->with('success', 'Candidacy withdrawn.');
    }
}

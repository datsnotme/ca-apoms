<?php

namespace App\Http\Controllers\Student;

use App\Enums\DocumentCategory;
use App\Enums\RoleName;
use App\Enums\StudentClassification;
use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StudentRequest;
use App\Models\Curriculum;
use App\Models\Department;
use App\Models\Program;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Models\YearLevel;
use App\Services\StudentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function __construct(private readonly StudentService $students) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Student::class);

        $students = Student::query()
            ->visibleTo($request->user())
            ->with(['department:id,name', 'program:id,name', 'yearLevel:id,label'])
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(fn ($q) => $q->where('student_number', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%"));
            })
            ->when($request->string('classification')->trim()->isNotEmpty(), fn ($q) => $q->where('classification', $request->string('classification')))
            ->when($request->string('status')->trim()->isNotEmpty(), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->integer('year_level_id'), fn ($q) => $q->where('year_level_id', $request->integer('year_level_id')))
            ->orderBy('surname')
            ->paginate(20)
            ->withQueryString();

        $students->getCollection()->transform(function (Student $student) use ($request) {
            $student->can_view_progress = $request->user()->can('viewProgress', $student);

            return $student;
        });

        return Inertia::render('Students/Index', [
            'students' => $students,
            'filters' => $request->only('search', 'classification', 'status', 'year_level_id'),
            'classifications' => array_map(fn ($c) => ['value' => $c->value, 'label' => $c->label()], StudentClassification::cases()),
            'statuses' => array_map(fn ($s) => ['value' => $s->value, 'label' => $s->label()], StudentStatus::cases()),
            'yearLevels' => YearLevel::query()->orderBy('level')->get(['id', 'label']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Student::class);

        return Inertia::render('Students/Create', $this->formOptions($request));
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $student = $this->students->create($request->validated(), $request->user()->id);

        return redirect()->route('students.edit', $student)->with('success', 'Student registered.');
    }

    public function edit(Request $request, Student $student): Response
    {
        $this->authorize('update', $student);

        $student->load([
            'guardians', 'addresses', 'statusHistories.changedBy:id,name',
            'documents' => fn ($query) => $query->latest('uploaded_at'),
            'documents.uploadedBy:id,name', 'documents.verifiedBy:id,name',
        ]);

        return Inertia::render('Students/Edit', [
            'student' => [
                ...$student->toArray(),
                'guardian' => $student->guardians->firstWhere('type', 'guardian'),
                'emergency' => $student->guardians->firstWhere('type', 'emergency_contact'),
                'permanent_address' => $student->addresses->firstWhere('type', 'permanent')?->address_line,
                'current_address' => $student->addresses->firstWhere('type', 'current')?->address_line,
                'status_histories' => $student->statusHistories,
                'can_view_progress' => $request->user()->can('viewProgress', $student),
            ],
            'documentCategories' => array_map(fn ($c) => ['value' => $c->value, 'label' => $c->label()], DocumentCategory::cases()),
            ...$this->formOptions($request),
        ]);
    }

    public function update(StudentRequest $request, Student $student): RedirectResponse
    {
        $this->students->update($student, $request->validated());

        return redirect()->route('students.edit', $student)->with('success', 'Student updated.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $this->authorize('delete', $student);

        $student->delete();

        return redirect()->route('students.index')->with('success', 'Student archived.');
    }

    /**
     * Same authorization and soft-delete behavior as destroy(), applied to a
     * batch. Authorization is checked for every record before any deletion
     * happens — if one record isn't deletable by this user, none are.
     */
    public function destroyMany(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:students,id'],
        ]);

        $students = Student::whereIn('id', $validated['ids'])->get();

        foreach ($students as $student) {
            $this->authorize('delete', $student);
        }

        foreach ($students as $student) {
            $student->delete();
        }

        return redirect()->route('students.index')->with('success', $students->count().' student(s) archived.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Request $request): array
    {
        return [
            'departments' => Department::query()->visibleTo($request->user())->orderBy('name')->get(['id', 'name']),
            'programs' => Program::query()
                ->whereHas('department', fn ($q) => $q->visibleTo($request->user()))
                ->orderBy('name')->get(['id', 'name', 'department_id']),
            'curricula' => Curriculum::query()
                ->visibleTo($request->user())
                ->orderBy('name')->get(['id', 'name', 'program_id']),
            'yearLevels' => YearLevel::query()->orderBy('level')->get(['id', 'level', 'label']),
            'sections' => Section::query()->orderBy('name')->get(['id', 'name', 'program_id', 'year_level_id']),
            'advisers' => User::query()
                ->role([RoleName::Faculty->value, RoleName::DepartmentHead->value])
                ->orderBy('surname')->get(['id', 'name']),
            'classifications' => array_map(fn ($c) => ['value' => $c->value, 'label' => $c->label()], StudentClassification::cases()),
            'statuses' => array_map(fn ($s) => ['value' => $s->value, 'label' => $s->label()], StudentStatus::cases()),
        ];
    }
}

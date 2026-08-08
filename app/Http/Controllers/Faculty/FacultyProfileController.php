<?php

namespace App\Http\Controllers\Faculty;

use App\Enums\FacultyDocumentCategory;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Faculty\FacultyProfileRequest;
use App\Models\FacultyDocument;
use App\Models\FacultyProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FacultyProfileController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', FacultyProfile::class);

        $faculty = User::query()
            ->role(RoleName::Faculty->value)
            ->with(['department:id,name', 'facultyProfile'])
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%"));
            })
            ->when(
                $request->user()->hasRole(RoleName::DepartmentHead->value),
                fn ($q) => $q->where('department_id', $request->user()->department_id)
            )
            ->when(
                $request->user()->hasRole(RoleName::Faculty->value),
                fn ($q) => $q->where('id', $request->user()->id)
            )
            ->orderBy('surname')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('FacultyProfiles/Index', [
            'faculty' => $faculty,
            'filters' => $request->only('search'),
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        abort_unless($user->hasRole(RoleName::Faculty->value), 404);

        $profile = FacultyProfile::firstOrCreate(['user_id' => $user->id]);
        $this->authorize('view', $profile);

        $user->load([
            'department:id,name',
            'education' => fn ($q) => $q->orderByDesc('year_completed'),
            'credentials' => fn ($q) => $q->orderByDesc('issued_date'),
            'trainings' => fn ($q) => $q->orderByDesc('start_date'),
            'awards' => fn ($q) => $q->orderByDesc('date_awarded'),
            'facultyDocuments' => fn ($q) => $q->with(['uploadedBy:id,name', 'verifiedBy:id,name'])->orderByDesc('uploaded_at'),
        ]);

        return Inertia::render('FacultyProfiles/Show', [
            'faculty' => $user,
            'profile' => $profile,
            'canManage' => $request->user()->can('faculty-profiles.manage'),
            'canEdit' => $request->user()->can('update', $profile),
            'canUploadDocuments' => $request->user()->can('upload', [FacultyDocument::class, $user]),
            'documentCategories' => collect(FacultyDocumentCategory::cases())
                ->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()]),
        ]);
    }

    public function update(FacultyProfileRequest $request, User $user): RedirectResponse
    {
        $profile = FacultyProfile::firstOrCreate(['user_id' => $user->id]);

        $profile->update($request->validated());

        return redirect()->route('faculty-profiles.show', $user)->with('success', 'Faculty profile updated.');
    }
}

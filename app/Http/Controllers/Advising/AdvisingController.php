<?php

namespace App\Http\Controllers\Advising;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdvisingController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('advising.view'), 403);

        $user = $request->user();

        $students = Student::query()
            ->with(['department:id,name', 'program:id,name', 'adviser:id,name', 'latestAdvisingRecord'])
            ->when(
                $user->hasRole(RoleName::Faculty->value),
                fn ($q) => $q->where('adviser_id', $user->id),
                fn ($q) => $q->when(
                    ! $user->hasRole([RoleName::Administrator->value, RoleName::Dean->value]),
                    fn ($q2) => $q2->where('department_id', $user->department_id)
                )
            )
            ->orderBy('surname')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Advising/Index', [
            'students' => $students,
            'canManage' => $user->can('advising.manage'),
        ]);
    }
}

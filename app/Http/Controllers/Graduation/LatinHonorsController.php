<?php

namespace App\Http\Controllers\Graduation;

use App\Http\Controllers\Controller;
use App\Services\LatinHonorsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LatinHonorsController extends Controller
{
    public function __construct(private readonly LatinHonorsService $latinHonors) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('graduation.view'), 403);

        $prospects = $this->latinHonors->identifyProspects($request->user())
            ->map(fn (array $row) => [
                'student' => [
                    'id' => $row['student']->id,
                    'student_number' => $row['student']->student_number,
                    'name' => $row['student']->name,
                    'department' => $row['student']->department,
                    'program' => $row['student']->program,
                    'year_level' => $row['student']->yearLevel,
                ],
                'gwa' => $row['gwa'],
                'completion_percentage' => $row['completion_percentage'],
            ]);

        return Inertia::render('GraduationCandidates/LatinHonors', [
            'prospects' => $prospects,
            'minGwa' => LatinHonorsService::MIN_QUALIFYING_GWA,
            'maxGwa' => LatinHonorsService::MAX_QUALIFYING_GWA,
        ]);
    }
}

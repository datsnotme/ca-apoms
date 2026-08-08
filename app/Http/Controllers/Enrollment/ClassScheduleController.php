<?php

namespace App\Http\Controllers\Enrollment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Enrollment\ClassScheduleRequest;
use App\Models\ClassSchedule;
use App\Models\ClassSection;
use Illuminate\Http\RedirectResponse;

class ClassScheduleController extends Controller
{
    public function store(ClassScheduleRequest $request, ClassSection $classSection): RedirectResponse
    {
        $classSection->schedules()->create($request->validated());

        return redirect()->route('class-sections.edit', $classSection)->with('success', 'Schedule added.');
    }

    public function destroy(ClassSection $classSection, ClassSchedule $schedule): RedirectResponse
    {
        $this->authorize('update', $classSection);

        abort_unless($schedule->class_section_id === $classSection->id, 404);

        $schedule->delete();

        return redirect()->route('class-sections.edit', $classSection)->with('success', 'Schedule removed.');
    }
}

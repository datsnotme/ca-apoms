<?php

namespace App\Services;

use App\Models\FacultyAssignment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Computes teaching load on demand from faculty_assignments (Phase 2E) —
 * no faculty_workloads table. See ASSUMPTIONS.md.
 */
class FacultyWorkloadService
{
    public function sectionsFor(User $faculty, ?int $semesterId): Collection
    {
        return FacultyAssignment::query()
            ->where('faculty_id', $faculty->id)
            ->when($semesterId, fn ($q) => $q->whereHas('classSection', fn ($q2) => $q2->where('semester_id', $semesterId)))
            ->with([
                'classSection.course:id,code,title,units',
                'classSection.semester:id,academic_year_id,term',
                'classSection.semester.academicYear:id,start_year,end_year',
                'classSection.schedules.facility:id,name',
            ])
            ->get()
            ->map(fn (FacultyAssignment $assignment) => [
                'id' => $assignment->id,
                'role' => $assignment->role->label(),
                'section_id' => $assignment->classSection->id,
                'course_code' => $assignment->classSection->course->code,
                'course_title' => $assignment->classSection->course->title,
                'units' => (float) $assignment->classSection->course->units,
                'section_label' => $assignment->classSection->section_label,
                'semester' => "{$assignment->classSection->semester->academicYear->start_year}-{$assignment->classSection->semester->academicYear->end_year} {$assignment->classSection->semester->term->label()}",
                'schedules' => $assignment->classSection->schedules->map(fn ($schedule) => [
                    'day' => $schedule->day_of_week->label(),
                    'start_time' => $schedule->start_time?->format('H:i'),
                    'end_time' => $schedule->end_time?->format('H:i'),
                    'room' => $schedule->facility?->name,
                ])->values(),
            ]);
    }

    public function totalUnits(Collection $sections): float
    {
        return round((float) $sections->sum('units'), 2);
    }

    /**
     * Same shape as calling sectionsFor()/totalUnits() once per faculty
     * member, but in one query instead of N — sectionsFor() is still used
     * as-is for the single-faculty detail view (FacultyWorkloadController::
     * show()), which needs the full per-section breakdown this summary
     * doesn't.
     *
     * @param  Collection<int, User>  $facultyMembers
     */
    public function summaryFor(Collection $facultyMembers, ?int $semesterId): Collection
    {
        $assignmentsByFaculty = FacultyAssignment::query()
            ->whereIn('faculty_id', $facultyMembers->pluck('id'))
            ->when($semesterId, fn ($q) => $q->whereHas('classSection', fn ($q2) => $q2->where('semester_id', $semesterId)))
            ->with('classSection.course:id,units')
            ->get()
            ->groupBy('faculty_id');

        return $facultyMembers->map(function (User $faculty) use ($assignmentsByFaculty) {
            $assignments = $assignmentsByFaculty->get($faculty->id, collect());

            return [
                'id' => $faculty->id,
                'name' => $faculty->name,
                'department' => $faculty->department?->name,
                'section_count' => $assignments->count(),
                'total_units' => round((float) $assignments->sum(fn (FacultyAssignment $a) => (float) $a->classSection->course->units), 2),
            ];
        })->values();
    }
}

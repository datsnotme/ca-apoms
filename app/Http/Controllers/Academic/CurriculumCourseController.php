<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\CurriculumCourseRequest;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use Illuminate\Http\RedirectResponse;

class CurriculumCourseController extends Controller
{
    public function store(CurriculumCourseRequest $request, Curriculum $curriculum): RedirectResponse
    {
        $curriculum->curriculumCourses()->create($request->validated());

        return redirect()->route('curricula.edit', $curriculum)->with('success', 'Course added to curriculum.');
    }

    public function update(CurriculumCourseRequest $request, Curriculum $curriculum, CurriculumCourse $curriculumCourse): RedirectResponse
    {
        abort_unless($curriculumCourse->curriculum_id === $curriculum->id, 404);

        $curriculumCourse->update($request->validated());

        return redirect()->route('curricula.edit', $curriculum)->with('success', 'Curriculum course updated.');
    }

    public function destroy(Curriculum $curriculum, CurriculumCourse $curriculumCourse): RedirectResponse
    {
        $this->authorize('update', $curriculum);

        abort_unless($curriculumCourse->curriculum_id === $curriculum->id, 404);

        $curriculumCourse->delete();

        return redirect()->route('curricula.edit', $curriculum)->with('success', 'Course removed from curriculum.');
    }
}

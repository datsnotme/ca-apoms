<?php

namespace App\Services\Import;

use App\Models\Student;
use App\Models\StudentHistoricalGrade;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Imports one student's prior-program grade record (e.g. a shiftee's
 * coursework under a program they're no longer in) from a spreadsheet
 * transcribed from a paper/PDF academic record. Deliberately scoped to a
 * single, already-known Student — unlike the generic ImportRunner pipeline
 * (Students/Courses/Enrollment/Grades), this never has to resolve "which
 * student does this row belong to" from the file itself, so it validates
 * every row against the ONE target student and rejects the whole upload if
 * any row's student_number or student_name doesn't match — a wrong-file
 * upload must never partially land on the wrong student's record. See
 * ASSUMPTIONS.md.
 */
class StudentHistoricalGradeImportService
{
    /**
     * @return array{imported: int} on success
     *
     * @throws ValidationException
     */
    public function import(Student $student, UploadedFile $file, User $actor): array
    {
        $sheet = new HeadingRowArrayImport;
        Excel::import($sheet, $file);

        if (count($sheet->rows) === 0) {
            throw ValidationException::withMessages(['file' => 'The uploaded file has no data rows.']);
        }

        $validatedRows = [];

        foreach ($sheet->rows as $index => $row) {
            $rowNumber = $index + 2;

            // Spreadsheet software stores a numeric-looking cell (e.g.
            // "2.50") as an actual number, so PhpSpreadsheet hands it back
            // as an int/float instead of a string — same normalization
            // GradesImporter applies.
            if (isset($row['grade']) && is_numeric($row['grade'])) {
                $row['grade'] = number_format((float) $row['grade'], 2, '.', '');
            }

            $validated = Validator::make($row, [
                'student_number' => ['required', 'string'],
                'student_name' => ['required', 'string'],
                'academic_year' => ['required', 'string'],
                'semester' => ['required', 'string'],
                'program' => ['nullable', 'string'],
                'course_code' => ['required', 'string'],
                'course_title' => ['required', 'string'],
                'lecture_hours' => ['nullable', 'numeric'],
                'laboratory_hours' => ['nullable', 'numeric'],
                'units' => ['required', 'numeric', 'min:0'],
                'grade' => ['nullable', 'string', 'max:10'],
            ], [], [
                'student_number' => "row {$rowNumber} student_number",
                'student_name' => "row {$rowNumber} student_name",
                'course_code' => "row {$rowNumber} course_code",
                'course_title' => "row {$rowNumber} course_title",
                'units' => "row {$rowNumber} units",
            ])->validate();

            if ($validated['student_number'] !== $student->student_number) {
                throw ValidationException::withMessages([
                    'file' => "Row {$rowNumber} is for student number {$validated['student_number']}, but you're importing into {$student->student_number} ({$student->name}). Upload rejected — no rows were saved.",
                ]);
            }

            if (! $this->nameMatches($validated['student_name'], $student)) {
                throw ValidationException::withMessages([
                    'file' => "Row {$rowNumber}'s student name (\"{$validated['student_name']}\") doesn't match {$student->name}. Upload rejected — no rows were saved.",
                ]);
            }

            $validatedRows[] = $validated;
        }

        return DB::transaction(function () use ($validatedRows, $student, $actor) {
            // A fresh upload replaces the prior import entirely rather than
            // appending — the source document is always the student's
            // complete prior-program history, not an incremental delta, so
            // re-uploading a corrected file must not leave stale duplicate
            // rows behind.
            $student->historicalGrades()->delete();

            foreach ($validatedRows as $row) {
                StudentHistoricalGrade::create([
                    'student_id' => $student->id,
                    'academic_year_label' => $row['academic_year'],
                    'semester_label' => $row['semester'],
                    'program_label' => $row['program'] ?? null,
                    'course_code' => $row['course_code'],
                    'course_title' => $row['course_title'],
                    'lecture_hours' => $row['lecture_hours'] ?? null,
                    'laboratory_hours' => $row['laboratory_hours'] ?? null,
                    'units' => $row['units'],
                    'grade' => $row['grade'] ?? null,
                    'imported_by' => $actor->id,
                ]);
            }

            return ['imported' => count($validatedRows)];
        });
    }

    /**
     * Forgiving on purpose — the source spreadsheet's name formatting won't
     * necessarily match the app's "First Middle Surname" convention (the
     * paper record uses "SURNAME, FIRST M."), so this checks that both the
     * surname and first name appear somewhere in the given string rather
     * than requiring an exact format match. Still a real safety gate: it
     * only passes when both name parts are actually present.
     */
    private function nameMatches(string $givenName, Student $student): bool
    {
        $normalized = strtoupper(preg_replace('/[^A-Z ]/i', ' ', $givenName));
        $normalized = preg_replace('/\s+/', ' ', trim($normalized));

        $surname = strtoupper($student->surname);
        $firstName = strtoupper($student->first_name);

        return str_contains($normalized, $surname) && str_contains($normalized, $firstName);
    }
}

# ASSUMPTIONS.md

Practical institutional decisions made where the spec did not explicitly specify a value.
Each is implemented **configurably** (a settings table, an enum with clear extension points,
or a seeded lookup table) rather than hard-coded, so the institution can change it without a
code change where reasonably possible.

## Organizational Structure

- **Single college, multiple departments.** The spec describes "the College of Agriculture"
  (singular) but names departments and programs (plural). Schema supports multiple colleges
  (`colleges` table) for future-proofing, but v1 seed data and UI assume one active college.
- **One role per user in v1.** The four roles are mutually exclusive by job function in the
  spec's own description. Implemented via `assignRole()` (Spatie), so a user *could* hold
  multiple roles later without a schema change, but the UI and seeders assume one.
- **A Department Head is a `users` row with role `department-head` and a `department_id`.**
  The `departments.department_head_id` FK is a *pointer* to whichever user currently holds
  that assignment — it is kept in sync when an admin assigns/reassigns a head, rather than
  being the sole source of truth for "who is the head" (the user's own role+department is
  the source of truth for access control; `department_head_id` is for display/reporting).

## Authentication

- **No student self-service login in v1**, per the spec. Students are data records only.
- **Admin-created accounts only** — Breeze's public registration route is removed. New
  accounts are created by a College Administrator via User Management.
- **Forced password change on first login** — every admin-created account has
  `must_change_password = true` until the user completes their first password change.
- **Account lockout**: 5 failed attempts within 15 minutes locks the account for 15 minutes
  (Laravel's built-in rate limiter, configurable via `config/auth.php` — not hard-coded
  numbers scattered through controllers).
- **Session timeout**: 120 minutes of inactivity (Laravel's default `session.lifetime`,
  overridable via `.env`).
- **Two-factor authentication**: architecture-ready (a `two_factor_secret` /
  `two_factor_recovery_codes` column pair, matching Laravel Fortify's convention) but not
  enabled/enforced in Phase 1 — no institution requirement was specified for which roles
  would need it.

## Grading and Academic Rules

- **Grading scale is configurable**, not hard-coded to one university's policy (per explicit
  spec instruction). A `grading_scales` + `grading_scale_values` pair of tables (added in
  Phase 2) lets the institution define numeric ranges and their pass/fail/incomplete
  meaning. Seed data ships with one reasonable Philippine-HEI-style default (1.00–5.00,
  3.00 passing) purely as a working example, clearly documented as sample data.
- **Workload load-per-unit formula is configurable** via a settings table read by
  `WorkloadService`, not hard-coded arithmetic in a controller (Phase 5).
- **Graduation requirement templates are configurable per program + curriculum** (Phase 4),
  not a single fixed checklist.

## Phase 2: Academic Records

- **No standalone `majors` table** — `programs.major` (a Phase 1 field) already captures
  "BS Agriculture major in Crop Science" vs. "...major in Animal Science." See
  `DATABASE_DESIGN.md`'s Phase 2 scoping notes.
- **`class_schedules.room` is free text**, not a `facilities` foreign key, since the
  Facilities module doesn't exist until Phase 7.
- **Grade submission/review/finalization happens per class section, not per student.** A
  faculty member submits an entire class's grades as one batch (`grade_submissions`); a
  Department Head reviews/returns/finalizes that same batch. Individual grade *values* still
  live on `student_grades` and every change is logged to `grade_change_logs` regardless of
  batch state — so a single-student grade change (e.g., resolving one INC) after the batch is
  finalized is a **reopen** action on that one `student_grades` row, not a reopen of the whole
  submission.
- **Only one grading scale is "active" at a time**, chosen by `grading_scales.is_default`.
  Historical grades keep whatever scale value they were recorded under even if the default
  scale changes later — `student_grades` does not store which scale was active when the grade
  was entered, since the seeded scale's value strings (1.00–5.00, INC, DRP, W, Credited, NG)
  are treated as institution-wide constants that a scale change would extend, not replace.
  If the institution ever needs literally incompatible grading vocabularies over time, that's
  a bigger change than Phase 2 scope.
- **A student may only have one active `student_enrollments` row per semester** (unique
  constraint), and only one active `enrollment_courses` row per course per semester unless its
  status is explicitly `Repeated` — enforced at the application layer (the "unless explicitly
  authorized" duplicate-enrollment exception the spec calls for isn't expressible as a plain
  DB unique constraint).
- **Student documents have no public URL under any circumstance** — files are stored on the
  `local` (non-public) disk and served through an authenticated, policy-checked download
  route, per the explicit security requirement.
- **`student_status_histories` is written automatically** whenever `students.status` changes
  via the edit form (previous value, new value, who changed it, when) — it is not a separate
  form staff fill out by hand.
- **Curriculum "effective academic year"** pins a curriculum version to when it took effect,
  but does **not** retroactively reassign already-enrolled students to a newer curriculum
  version when one is published — a student's `curriculum_id` only changes via the explicit
  "Change curriculum" action (a Phase 2 student function from the spec), never automatically.
- **Student document categories** are a fixed backed enum (birth certificate, Form 137/138,
  good moral certificate, medical certificate, ID photo, transcript of records, certificate of
  registration, other) rather than a free-text field or a separate lookup table, matching the
  same "backed enum stored as a plain string" pattern used for classification/status. It can be
  extended with new cases without a schema change.
- **`student_documents.visibility_level`** is stored (per the DATABASE_DESIGN.md schema) but not
  yet differentiated in application logic — CA-APOMS has no student-facing portal in the current
  role set (Admin/Dean/Department Head/Faculty only), so every document is effectively
  `staff_only` today. The column exists so a future student-portal phase can add
  student-visible documents without a migration.
- **Only the Administrator can upload, verify/reject, or delete student documents.** A
  Department Head can view and download documents for students in their own department but
  cannot manage them — this mirrors the `student-documents.manage`/`.view` permission split
  already seeded in `RolesAndPermissionsSeeder` and documented in `ROLE_PERMISSIONS.md`. Dean
  and Faculty have no access to student documents at all in Phase 2.
- **Deleting a document removes the physical file from disk immediately** (not just the DB row)
  even though the DB row itself is soft-deleted — verified documents are expected to be
  re-uploaded rather than restored, since a rejected/removed document usually means the
  physical copy was wrong or superseded.
- **Class section management and student enrollment share one permission** (`enrollment.manage`)
  rather than being split into separate `class-sections.manage` / `enrollment.manage`
  permissions. In this phase both are the same registrar-style function performed by the same
  people (Admin, Department Head); splitting them would add permission strings with no role
  ever needing one without the other. Revisit if a future role needs one but not the other.
- **A class section's primary faculty assignment is edited directly on the class section form**
  (a single `faculty_id` dropdown), not through a separate faculty-assignment screen, even
  though `faculty_assignments` is a real table (per `DATABASE_DESIGN.md`, kept as its own table
  for Phase 5 workload reasons). Phase 2 only ever writes one `role = primary` row per section;
  co-faculty assignment and workload views are Phase 5 scope.
- **Duplicate-enrollment and capacity checks live in `EnrollmentService`, not a DB constraint.**
  "At most one active `enrollment_courses` row per course per semester unless `Repeated`" and
  "a class section can't exceed `max_students`" both require reading related rows (the course
  behind a class section, the count of active enrollments) that a plain unique/check constraint
  can't express. "Active" excludes `Dropped`, `Withdrawn`, `Failed`, `Incomplete`, and
  `Credited` — a student can freely re-add a course they dropped or failed without checking the
  repeat box, since only a still-active enrollment counts as a genuine duplicate.
- **`class_sections` enforces one label per course per semester via a DB unique constraint**
  (`course_id`, `semester_id`, `section_label`), since that rule needs no related-table lookups
  and a DB constraint is strictly more reliable than an app-layer check for it.
- **Grade workflow abilities live on `ClassSectionPolicy`, not a separate `GradeSubmissionPolicy`**
  (`viewGrades`/`encodeGrades`/`reviewGrades`). Laravel resolves exactly one policy per model
  class by convention, and the grade workflow's natural subject is the `ClassSection` it
  batches on — a second policy for the same model would never be discovered.
  `reviewGrades` is shared by approve, return, finalize, and single-row correction, since
  `ASSUMPTIONS.md` already establishes the Department Head as the one authority across that
  whole review→finalize pipeline.
- **A post-finalization grade fix is a single-step "correction," not a two-phase reopen.** The
  ERD's `student_grades.status` enum includes `locked` as a reserved value for a stricter,
  permanently-closed state (e.g. once grades are exported to an official transcript), but Phase
  2F never transitions into it — `finalized` is the practical terminal state a class submission
  reaches, and `correct()` updates the grade value and writes a `grade_change_logs` row (with a
  required reason, `changed_by`, and `approved_by` all set to the corrector) while the row's
  status stays `finalized` throughout. This avoids a window where a single "reopened" row
  could be mistaken for a whole class being reopened.
- **Every grade *value* change is logged**, including the very first time a grade is encoded
  (`previous_grade` is `null`) — not just post-finalization corrections — per the explicit
  design note in the Phase 2 ERD. Implementation note for future model-event code: Eloquent's
  `wasChanged()` is not reliably populated after a fresh `INSERT` in this Laravel version (only
  after `UPDATE`s), so `StudentGrade` captures dirty-state via `isDirty()`/`getOriginal()` in a
  `saving` hook and reads that captured value back in `saved`, rather than trusting
  `wasChanged()` directly in `saved`.
- **`grades.import` is seeded as a Faculty permission** (mirroring `students.import` being
  Admin-only) since the spec's Excel-import module (Phase 2G) is expected to let faculty import
  their own class's grades from a spreadsheet, not just encode them one row at a time.
- **Excel imports validate and persist row-by-row, not the whole file in one transaction.**
  Each row is independently validated, then (if valid) written inside its own DB transaction;
  a business-rule failure discovered only at write time (e.g. a duplicate enrollment or a
  finalized grade submission) reports as a single row error rather than discarding every other
  valid row in the file. There is no separate "preview, then confirm" step — the
  `import_batches.status` enum (`processing|completed|failed`) has no pending/preview state, so
  upload immediately processes the file and the results screen (imported/skipped counts + a
  downloadable per-row error report) *is* the review step. Re-running the same file is
  idempotent, since every importer upserts by natural key (student number, course code,
  curriculum+course pair, etc.) rather than blind-inserting.
- **A numeric-looking grade value (e.g. `1.00`) is reconstructed from spreadsheet cells, not
  trusted as-is.** Excel/Google Sheets stores a cell like `1.00` as the number `1`, so
  PhpSpreadsheet hands the import code back an int/float, not the string `"1.00"` the grading
  scale expects — comparing that raw value against the scale's values would reject every
  legitimate numeric grade. `GradesImporter` reformats any numeric cell to two decimal places
  before validating. The same class of risk exists for any other "numeric-looking but really a
  code" column (e.g. an all-digit student number losing leading zeros) — not yet hit in
  practice since this institution's student/course/department codes are alphanumeric, but worth
  remembering if a future import type needs a purely numeric natural key.
- **Reused business-rule services inside importers, rather than re-implementing them.**
  `EnrollmentImporter` calls `EnrollmentService::addCourse()` and `GradesImporter` calls
  `GradeService::encode()` — the exact same code paths the manual UI uses — so a bulk import
  can never bypass a rule (capacity, duplicate enrollment, grade-editable-only-while-draft)
  that manual entry enforces. A rule violation surfaces as a normal row-level import error.
- **A new `progress.view` permission, distinct from `students.view`.** The spec's own matrix
  scopes Faculty to *their own advisees* for viewing progress/deficiencies, narrower than the
  department-wide access `students.view` already grants Faculty in Phase 2. Reusing
  `students.view` would have over-shared it, so Phase 3A adds a permission whose Faculty scope
  is `student.adviser_id === auth()->id()` specifically, not department membership.
- **Checklist status per course uses "best attempt wins," computed fresh on every page load.**
  A curriculum course can have multiple `enrollment_courses` attempts across semesters (a
  retake after failing). `ProgressComputationService` picks a passing attempt over a failing one
  if both exist, rather than whichever was most recent — a student who failed once and passed
  the retake should show `completed`, not `failed`. Nothing is cached, so there's no staleness
  window between a grade being finalized and the checklist reflecting it.
- **A deficiency requires the course's expected year level to have already passed** (per the
  curriculum, `curriculum_courses.year_level < student's current year level`). A required course
  the student hasn't reached yet is `pending` on the checklist, not a deficiency, even if
  `not_taken` — flagging it early would create false alarms for perfectly on-track students.
- **Adviser assignment reuses the existing `students.adviser_id` field and `students.manage`
  permission rather than a new dedicated workflow.** `student_advisers` (Phase 3B) is a
  read-only history log auto-written by a `Student` model event whenever `adviser_id` changes —
  mirroring `student_status_histories` exactly. This avoids two write paths for the same fact
  (who is a student's adviser) ever going out of sync, and means no new UI was needed beyond the
  adviser dropdown the Student edit form already had since Phase 2C.
- **Advising records live on the same page as Progress, not a separate module page.** The
  motivation for logging an advising session — a deficiency, a low GWA, a stalled checklist — is
  the data the Progress page already shows, so keeping them together avoids forcing an adviser
  to cross-reference two pages. The "Advising" nav item instead lists *whose* progress to look
  at ("My Advisees"), not a duplicate of the session log itself.
- **`advising.manage` is granted to Admin, Department Head, and Faculty, but not Dean** — the
  spec explicitly scopes "record advising/intervention" that way, distinct from `progress.view`
  and `advising.view`, which the Dean does get (college-wide, read-only oversight).
- **A Faculty adviser can only manage sessions for their *current* advisees**
  (`student.adviser_id === auth()->id()` at the time of the request), not students they advised
  in the past before a reassignment. Historical sessions they logged remain visible to whoever
  can currently view that student's advising history (Admin/Dept Head/Dean), just not editable
  by the original adviser once reassigned — consistent with the DB not tracking "who was allowed
  to log a session on the day it was written," only who is allowed to manage records now.
- **At-risk thresholds are fixed constants in `ProgressAlertService`, not an admin-configurable
  setting.** The spec asks for "deficiency count" and "GWA threshold" rules without specifying
  an institution-editable policy for them (unlike the grading scale, which the spec explicitly
  calls out as needing to be configurable). Named class constants keep the thresholds easy to
  find and change in code without inventing UI/storage for a setting nobody asked to control at
  runtime — revisit if a later phase's requirements say otherwise.
- **Alert re-sync happens on page visit, scoped to the viewer, not as a background job.** The
  at-risk list re-evaluates every student the current viewer can see (their advisees, their
  department, or the whole college) each time they open the page. This is deliberately the same
  "computed on demand" shape as deficiency syncing, and is bounded by the viewer's own
  visibility scope rather than the whole student body — an Admin/Dean visiting the page is the
  only case that touches every student at once. If the college's student count grows by an
  order of magnitude, this is the first place to move to a queued nightly job instead of
  per-request evaluation; not done now because there's no evidence yet that it's needed.
- **`multiple_deficiencies`, `low_gwa`, and `enrollment_status` are the only three alert types.**
  The spec's "at-risk monitoring" language doesn't enumerate specific rules, so these three were
  chosen because Phase 3A/3B already compute everything they need (deficiency count, GWA,
  `students.status`) — no new data collection was required to ship them. Additional rules (e.g.
  a genuine multi-semester enrollment gap, mirroring the "stopped" concept from a related
  evaluation-system project) are a natural Phase 3D-or-later extension, not built here because
  the underlying gap-detection logic doesn't exist yet in this codebase.
- **Intervention follow-ups reuse `advising.manage`/`advising.view`** rather than a dedicated
  permission — the spec's own matrix lists "record advising/intervention" as a single line, not
  two capabilities. `StudentInterventionFollowupPolicy` mirrors `StudentAdvisingRecordPolicy`'s
  scoping exactly, plus lets whoever a follow-up is `assigned_to` update it even if they aren't
  the student's adviser (a tutor asked to run point on one task shouldn't need to *be* the
  adviser to close it out).
- **A follow-up's `description` is required to create one but optional to update one.** The
  quick status-change controls in the UI (Start / Complete / Cancel) send only `{status}` — a
  genuine partial update, not a resubmission of the whole record. `StudentInterventionFollowupRequest`
  branches its validation on whether the route has a `{followup}` (update) or not (create) so
  the same FormRequest correctly serves both call shapes.
- **"Progress reports" means the existing consolidated Progress page (checklist + GWA +
  deficiencies + alerts + advising + follow-ups), not a new generated document.** Phase 3D adds
  a plain browser-print affordance instead of a PDF, since `barryvdh/laravel-dompdf` is
  explicitly slated for Phase 4 in `PROJECT_PLAN.md` — building PDF export now would front-run
  the plan for a format nothing in Phase 3 actually requires.
- **No `student_progress_snapshots` table.** It was on the original Phase 3 table list, but
  every value it would have stored (checklist status, GWA, completion percentage) is already
  computed on demand and shown live — snapshotting only earns its keep once something needs a
  point-in-time history to chart *over* time (a GWA trend line, a completion-rate dashboard),
  which is Phase 8 (Analytics) work, not Phase 3's. Adding the table now with nothing reading
  from it would be exactly the kind of premature abstraction this project avoids elsewhere.
- **`gwa_snapshot`, `completion_percentage_snapshot`, and `deficiency_count_snapshot` on
  `GraduationCandidate` are the one deliberate exception to "compute on demand, never persist."**
  Every other computed value in this project (checklist status, GWA, completion %, alerts) is
  always live. A graduation candidacy is different: once a student is nominated, the record of
  *why* they were nominated must not silently change if a grade is edited months later — a
  graduation packet should reflect the standing at nomination time, not drift underneath an
  approval already in progress. The live, current values remain one click away via the "View
  Progress" link on the candidate page, so freezing the snapshot loses nothing while protecting
  the historical record's integrity.
- **No `graduation_evaluations` table.** `GraduationCandidate.status`
  (`nominated → under_evaluation → recommended → approved/rejected → graduated`) is the single
  umbrella status field, mirroring the "no redundant status table" decision already made for
  `student_progress_snapshots` in Phase 3A. Phase 4B (competency evaluation) and 4C (department
  recommendation / Dean approval) will advance this same column rather than introduce a parallel
  evaluation-state table.
- **`graduation.view`/`graduation.manage` cover both requirement templates and candidates** —
  the spec groups "define graduation requirements" and "identify/nominate candidates" under one
  capability area, so a second permission pair was not introduced. Dean and Department Head are
  granted `.view` only in Phase 4A; only Administrator gets `.manage`, and both
  `GraduationCandidatePolicy` and `GraduationRequirementTemplatePolicy` additionally hard-require
  the Administrator role on every create/update/delete action. This is stricter than the
  permission alone would imply, because Phase 4's spec matrix treats "prepare/nominate" as an
  Admin-only responsibility, distinct from "recommend" (Department Head, Phase 4C) and "approve"
  (Dean, Phase 4C) — granting `.manage` to those roles now would let them nominate/edit
  requirements, which the spec reserves for later, narrower actions.
- **Eligibility for nomination is computed, not stored**, and requires both 100% checklist
  completion (`ProgressComputationService::completionPercentage() === 100.0`) and zero unresolved
  `academic_deficiencies` — reusing Phase 3A's engine rather than duplicating its logic. A
  student who is 100% complete but still carries an unresolved deficiency (e.g. a failed
  retake never re-attempted) is correctly excluded.
- **A student can only be nominated once while an active (non-`rejected`) candidacy exists.**
  `GraduationCandidateService::nominate()` throws a validation error on a duplicate attempt
  rather than silently creating a second row; a `rejected` candidacy does not block a fresh
  nomination in a later term, since rejection is a terminal-but-not-permanent outcome (e.g. a
  requirement gap was later fixed).
- **`student_graduation_requirements` rows are generated once, at nomination time, from whichever
  `graduation_requirement_templates` apply** (`program_id` null, or matching the student's own
  `program_id`) — not recomputed later. A requirement template created after a student is already
  nominated does not retroactively attach to their checklist; it only applies to future
  nominations. This keeps a candidate's checklist stable once evaluation begins, consistent with
  the same "freeze at nomination" reasoning as the snapshot fields above.
- **Assigning a candidate's first competency evaluator transitions `GraduationCandidate.status`
  from `nominated` to `under_evaluation` automatically**, rather than requiring a separate
  manual "start evaluation" action — mirroring the "a meaningful action triggers the status"
  shape already used for `student_advisers` in Phase 3B (reassigning an adviser auto-writes
  history; here, assigning an evaluator auto-advances status).
- **Competency evaluators must be Faculty from the same department as the candidate's student.**
  The spec frames competency evaluation as a departmental panel judgment, not a college-wide
  one; `CompetencyEvaluatorRequest` enforces both the role and department match server-side
  (not just via the UI dropdown's available-options list), since the dropdown alone would not
  stop a crafted request.
- **`GraduationCandidate::evaluationComplete()` is computed on demand**, true only once at least
  one evaluator is assigned and every assigned evaluator has rated every currently-defined
  competency indicator — the same "compute, don't persist a completion flag" approach as
  `requirementsComplete()` (Phase 4A) and the checklist engine (Phase 3A). A competency
  indicator added after evaluation has started correctly reopens `evaluationComplete()` to
  `false` until existing evaluators also rate the new indicator — there is no snapshot of
  "which indicators existed when evaluation began."
- **No separate "My Evaluations" page for Faculty.** Rather than building a parallel view,
  `graduation.view` is extended to Faculty and `GraduationCandidate::scopeVisibleTo()` /
  `GraduationCandidatePolicy::view()` are extended so Faculty's existing
  `/graduation-candidates` index and show page are scoped to only the candidates where they
  hold a `competency_evaluators` row. This reuses the exact same list/detail UI every other
  role already uses instead of duplicating it, at the cost of Faculty also being able to view
  (read-only) the Graduation Requirements and Competency Framework settings pages, since both
  now key off the same `graduation.view` permission — considered an acceptable, non-sensitive
  side effect rather than worth a fifth permission to prevent.
- **A new `graduation.evaluate` permission, granted to Faculty only**, gates rating submission —
  distinct from `graduation.view` (seeing a candidate) and `graduation.manage` (nominating,
  editing checklists, assigning evaluators). `CompetencyRatingRequest` additionally checks that
  the authenticated user holds the specific `competency_evaluators` row for that candidate, so
  the permission alone is not sufficient — a Faculty member cannot rate a candidate they were
  never assigned to, even though `graduation.evaluate` is a blanket role grant.
- **Removing an evaluator does not revert `GraduationCandidate.status` back to `nominated`**,
  even if it was the only evaluator assigned. Evaluation having *started* is treated as a fact
  about the candidate's timeline, not something an assignment change undoes — consistent with
  status fields elsewhere in this project never moving backward as a side effect of cleanup.
- **No `graduation_recommendations`/`graduation_approvals` tables**, despite both being on the
  original Phase 1 forward-looking table list (`DATABASE_DESIGN.md`'s "Planned Schema by
  Domain"). A candidate only ever receives one recommendation and one decision: a rejected
  candidacy is terminal per the Phase 4A nomination rules (a fresh attempt is a brand-new
  `GraduationCandidate` row, not a retry on the same one), so there is no multi-row history to
  capture. Six nullable columns were added to `graduation_candidates` instead
  (`recommended_by`/`recommended_at`/`recommendation_remarks`,
  `decided_by`/`decided_at`/`decision_remarks`), and `LogsActivity` (already on the model)
  captures the field-level audit trail for free. Same reasoning as skipping
  `graduation_evaluations` in Phase 4A and `student_progress_snapshots` in Phase 3A — this
  project only introduces a table once something needs to read history *from* it, not on
  spec-anticipation alone.
- **`recommend`/`decide` are new Policy ability methods on `GraduationCandidatePolicy`, not new
  permissions.** Both reuse the `graduation.view` grant Department Head and Dean already hold
  (from Phase 4A/4B), narrowed by role and, for Department Head, department match against the
  candidate's student. `graduation.manage` is deliberately excluded from both — recommending
  and approving are the Department Head's and Dean's own judgment calls on a candidate, not an
  Admin "manage the record" action, so Admin cannot recommend or approve even though Admin has
  `graduation.manage`. This mirrors Phase 4A's exclusion of Dept Head/Dean from nomination: each
  role gets exactly its own step in the pipeline, never another role's.
- **Recommending is gated on `GraduationCandidate::readyForRecommendation()`**
  (`requirementsComplete() && evaluationComplete()`), enforced in
  `GraduationRecommendationService::recommend()`, not only via the UI hiding the button. A
  Department Head who somehow posts directly to the recommend route before both gates clear
  gets a validation error, not a silently-accepted recommendation.
- **A Department Head has no "not recommend"/reject action** — only "Recommend." The spec's
  permission matrix lists Department Head's Phase 4 involvement as "recommend" specifically,
  with rejection reserved for the Dean (`graduation.manage`'s "Approve/reject graduating
  recommendations" row is Dean-only). If a Department Head believes a candidate should not
  proceed, the existing Admin-only "Withdraw" action on the candidate record is the intended
  path, not a new Department Head-initiated rejection.
- **`GraduationCandidateStatus::Graduated` requires a distinct, explicit confirmation step**
  (`GraduationRecommendationService::markGraduated()`), not an automatic side effect of Dean
  approval. Approval and actual conferral are different real-world events (a Dean can approve
  weeks before a commencement ceremony formally confers the degree), so collapsing them into one
  status transition would lose that distinction. The action is Admin-only, reusing the existing
  `update` gate (`graduation.manage` + Administrator role) rather than a new permission — treated
  as a registrar-style edit to the candidate record, the same reuse pattern already used for
  evaluator assignment (Phase 4B).
- **`barryvdh/laravel-dompdf` is the project's first PDF dependency**, introduced here per
  `PROJECT_PLAN.md`'s technology table, which explicitly reserved PDF generation for Phase 4
  rather than earlier phases building it ad hoc (see the Phase 3D note about the browser-print
  affordance standing in for PDF until this phase).
- **Graduation reports are plain Blade views, not Inertia pages.** dompdf renders server-side
  HTML to PDF and has no use for React/Inertia's client-side rendering, so
  `resources/views/pdf/*.blade.php` are the first non-Inertia views added to the app since the
  initial Breeze scaffold — a deliberate, narrow exception to the project's all-Inertia frontend,
  scoped strictly to file downloads.
- **The batch graduation-list report has no dedicated authorization logic.** It reuses
  `GraduationCandidate::scopeVisibleTo()` — the exact same scope the candidate index page already
  applies — so a Department Head requesting the batch PDF for a term only ever receives their own
  department's `approved`/`graduated` candidates, without a second, parallel scoping rule to keep
  in sync with the first.
- **The individual PDF report reuses the candidate's `view` policy check** (same as the show
  page) rather than a separate report-viewing permission — if a role can see a candidate on
  screen, they can download its report; nothing new is exposed.
- **`faculty_profiles` rows are lazily created on first view/edit**, not provisioned when a
  Faculty account is created or role-assigned. `FacultyProfileController` calls
  `FacultyProfile::firstOrCreate()`, the same "compute/create on demand" shape used throughout
  this project — no backfill migration, no `User` model boot-event side effect tying account
  creation to profile creation (a Faculty role can be granted/revoked independently of whether a
  profile row exists yet).
- **`academic_rank` is a plain string, not an enum or a configurable lookup table.** Unlike the
  grading scale — which the spec explicitly calls out as needing to be institution-configurable —
  nothing in the spec says academic ranking needs runtime configuration, and ranking schemes
  differ enough across institutions (Instructor I–III vs. Assistant/Associate/Full Professor vs.
  others) that hard-coding an enum would bake in an assumption the project has no basis for.
  Revisit if a later requirement calls for a configurable rank list.
- **`employment_status` is a backed enum** (`full_time`/`part_time`/`visiting`/`on_leave`),
  unlike `academic_rank`, because it's a genuinely small, fixed set — the same reasoning applied
  to every other status enum in this project (`ActiveStatus`, `GraduationCandidateStatus`, etc.).
- **A faculty member editing their own profile can only change `specialization`,
  `office_location`, and `bio`** — `academic_rank`, `employment_status`, and `date_hired` are
  treated as HR-controlled facts about employment, not self-reported ones. This is enforced by
  `FacultyProfileRequest::rules()` omitting those keys entirely for non-Admin actors (so
  `$request->validated()` never contains them, even if a crafted request includes them), not
  merely by hiding the fields in the UI.
- **No new permission for self-editing.** `FacultyProfilePolicy::update()` allows the action if
  the actor holds `faculty-profiles.manage` (Admin) **or** owns the profile
  (`$profile->user_id === $user->id`) — the same "permission check OR self-ownership check"
  shape already used for updating one's own basic account via `ProfileController`, just applied
  to this new resource instead of introducing a `faculty-profiles.manage-own` permission that
  would only ever be checked against the current user anyway.
- **`FacultyProfile::scopeVisibleTo()` mirrors `GraduationCandidate::scopeVisibleTo()`'s
  three-tier shape** (Admin/Dean unrestricted, Department Head own department, everyone else
  scoped to themselves) rather than inventing a new scoping pattern — Faculty Profiles is the
  second resource in this project (after Graduation Candidates) where the viewer's own role
  determines both "can view" and "which rows," and reusing the exact same branching keeps the
  authorization model predictable across modules.
- **`faculty_education`/`faculty_credentials`/`faculty_trainings`/`faculty_awards` key on
  `user_id` directly, not `faculty_profile_id`.** These records belong to the faculty member,
  not to the profile row specifically — the same modeling choice as `student_documents` keying
  on `student_id` rather than some other per-student wrapper table (Phase 2D). This also means
  these four tables don't depend on a `FacultyProfile` row existing at all; they'd work even if
  the profile's `firstOrCreate` lazy-creation pattern changed later.
- **Education/credentials/trainings/awards are Admin-only to create, edit, or delete — with no
  Faculty self-edit carve-out**, unlike the core profile fields from Phase 5A
  (specialization/office/bio). These four are treated as verifiable institutional records
  (transcripts, PRC licenses, certificates of completion, award citations) rather than
  self-reported facts about oneself, so the same "let the owner touch a few low-stakes fields"
  reasoning that justified Faculty self-edit on the profile doesn't apply — a faculty member
  self-reporting a fabricated credential is a materially different risk than them writing their
  own bio. Dean, Department Head, and the Faculty member themselves all get read-only visibility
  through the existing `faculty-profiles.view` permission; no `.view`/`.manage` split was needed
  per-resource since none of the four introduce a new visibility rule beyond "can you see this
  faculty member's profile at all."
- **`level` on `faculty_education` is a backed enum, but `training_type` on `faculty_trainings`
  is a free string.** Degree levels are a genuinely small, fixed, well-known set
  (bachelor's/master's/doctorate); training types (seminar, workshop, conference, webinar, study
  grant, etc.) are open-ended and institution-specific enough that hard-coding a list would be
  presumptuous — same reasoning already applied to `employment_status` (enum) vs. `academic_rank`
  (string) in Phase 5A.
- **None of the four child models have a `view`/`viewAny` Policy method.** Whether a viewer can
  see a faculty member's education/credentials/trainings/awards is decided entirely by
  `FacultyProfilePolicy::view()` when `FacultyProfileController@show` loads the page — the same
  "the parent's authorization is the only gate; child records don't need their own" pattern as
  `StudentGraduationRequirementPolicy` and `CompetencyIndicatorPolicy` in Phase 4, which likewise
  define only `create`/`update`/`delete`.

### Phase 5C: Faculty Documents

- **Faculty MAY self-upload their own documents, unlike Phase 5B's Admin-only
  education/credentials/trainings/awards.** The distinction is the `verification_status`
  workflow: a `faculty_documents` row starts `pending` regardless of who uploaded it, and it
  only becomes an asserted institutional fact once an Admin explicitly sets it to `verified`.
  Uploading is just submitting evidence for review, not asserting truth — so the Phase 5B "no
  self-entry for records presented as verified fact" rule doesn't apply here, because a
  self-uploaded document is never presented as verified until an Admin says so.
  `FacultyDocumentPolicy::upload()` therefore uses the same self-ownership-OR-permission shape
  as `FacultyProfilePolicy::update()` from Phase 5A (`$user->can('faculty-profiles.manage') ||
  $document->user_id === $user->id`), even though the two resources otherwise sit on opposite
  sides of the Phase 5B self-edit line.
- **`verify()` and `delete()` on `FacultyDocumentPolicy` remain Admin-only**, including for the
  faculty member who uploaded their own document — self-upload does not imply self-verification
  or self-deletion. A Department Head can view and download documents for their own department's
  faculty but cannot upload, verify, or delete, matching the read-only visibility they already
  have over the rest of the Faculty Management module.
- **`verification_status` reuses the `DocumentVerificationStatus` enum from Phase 2D**
  (`student_documents`) rather than defining a parallel enum — same three-state
  pending/verified/rejected shape, same reviewer + remarks pattern (`verified_by`, `verified_at`,
  `remarks`).
- **No `visibility_level` column**, unlike `student_documents`. That column exists on student
  documents for a (future) student-facing portal concept where a document's visibility to the
  student themselves is a separate axis from staff visibility. Faculty already log in directly
  with role-scoped access via `FacultyDocument::scopeVisibleTo()`; there is no analogous "hide
  this from the record owner" need, so adding the column now would be speculative.
- **`faculty_documents` uses soft deletes**, unlike the Phase 5B tables. Deleting a document also
  removes its file from storage (`FacultyDocumentController::destroy()`), so the DB row is kept
  as a tombstone (who deleted what, when) even though the underlying file is gone — the Phase 5B
  tables have no associated file to lose, so a hard delete there loses nothing worth keeping.

### Phase 5D: Faculty Workload

- **No `faculty_workloads` table.** Total teaching load is a pure aggregation over
  `faculty_assignments` (Phase 2E) → `class_sections` → `courses.units`, recomputed on every
  page load by `App\Services\FacultyWorkloadService`. Persisting it would just be a cache that
  goes stale the moment a class section is reassigned or a course's unit value changes — the
  same "computed on demand" reasoning already applied to graduation recommendation/approval
  state (Phase 4C) and student progress checklists (Phase 3A).
- **A section's full unit value counts toward whoever is assigned, regardless of `primary` vs
  `co-faculty` role.** Splitting units between co-faculty on the same section isn't part of the
  spec and no seeded/real data suggested a fractional convention, so the simplest correct
  behavior (count it in full for every assignee) was used rather than inventing a split rule
  nobody asked for.
- **The workload dashboard permission is `faculty-profiles.view`, reused as-is** — no new
  `faculty-workload.view` permission. The spec's own permission matrix scopes "Review faculty
  workloads" identically to "Manage faculty profiles" (Admin full, Dean college-wide, Department
  Head own department, Faculty own only — see `ROLE_PERMISSIONS.md`), so a second permission
  string would duplicate a scoping rule that already exists.
- **One route, two renderings, chosen server-side by role** — `FacultyWorkloadController::index()`
  returns "My Classes" (a Faculty member's own sections) for the Faculty role and a
  faculty-by-faculty dashboard for everyone else who can view it, rather than two separate
  routes/pages. This mirrors `AdvisingController::index()` (Phase 3), which already renders one
  page differently depending on whether the viewer is the adviser or a reviewer.
- **The dashboard is built from `users` (scoped by role/department), not from
  `faculty_assignments`.** Querying assignments first would silently omit any faculty member
  with zero assigned sections for the selected semester — exactly the faculty an actual workload
  review most needs to surface, so the query starts from who *can* teach, not from who currently
  *is* teaching.
- **No `FacultyWorkloadPolicy`.** Nothing here is a single authorizable Eloquent model; it's an
  aggregate read over `faculty_assignments`. Authorization is an inline permission check plus a
  manual department/self comparison in the controller, the same shape `AdvisingController`
  already established for a read-only, non-model-backed view.
- **The semester filter defaults to whichever `Semester` has `is_current = true`** (falling back
  to the most recently created semester if none is marked current), unlike
  `ClassSectionController`'s equivalent filter, which defaults to showing every semester. A
  workload review is almost always about "this term" specifically, not a full history browse, so
  defaulting narrow (and letting the dropdown widen it) fits this view better than the
  show-everything default used for browsing class sections.

### Phase 6A: Announcements and Events

- **Phase 6 was broken into six sub-phases (6A Announcements/Events, 6B Meetings, 6C Tasks, 6D
  Internal Requests, 6E Document Repository, 6F Notifications) before any of it was built**, the
  same way Phase 2 (7 sub-phases) and Phase 5 (4 sub-phases) were scoped up front — "College
  Operations" is too broad a module to build as one unit and still keep each sub-phase reviewable
  on its own.
- **One permission pair (`operations.manage`/`operations.view`) covers both Announcements and
  Events**, not four separate permissions. The spec's own permission matrix already scopes
  "Manage announcements/calendar" as a single capability across both concepts, so splitting them
  would invent a distinction the spec doesn't draw.
- **`department_id` is nullable rather than using a separate `is_college_wide` flag.** `null`
  already unambiguously means "entire college" — a boolean alongside a non-null `department_id`
  would just be a second way to express the same fact, with a "both set" state that means nothing
  and would need to be guarded against.
- **A Department Head can manage items within their own department but can never touch a
  college-wide item, even one about their own department.** This mirrors
  `ClassSectionPolicy::update()` (Phase 2E) exactly: Admin unrestricted, Department Head limited
  to `department_id === user.department_id` with no carve-out for null. Promoting a
  department-scoped announcement to college-wide, or editing an existing college-wide one, stays
  an Admin-only act, since it affects visibility outside the Department Head's own scope.
- **Dean is view-only for this capability**, matching the spec's row exactly (Admin ✅, Dean 👁,
  Department Head 🟡 own dept., Faculty 👁) — the same "Dean approves/oversees but doesn't run
  day-to-day departmental operations" posture already established for Advising (Phase 3, Dean has
  no `advising.manage`) and every other 🟡-vs-👁 row in the matrix.
- **No `show` page for either resource.** The Index list already renders each item's full title
  and body/description (not truncated), so a separate read-only detail page would just duplicate
  what's already visible in the list — the same reasoning `DepartmentController`/
  `ProgramController` already applied (`Route::resource(...)->except(['show'])`).
- **Events store and edit `start_at`/`end_at` as plain wall-clock values in the app's configured
  timezone (UTC), but the read-only Index list renders them through the browser's
  `toLocaleString()`, which converts to the viewer's local timezone for display only.** The Edit
  form deliberately does *not* apply this conversion (it round-trips the exact stored value), so
  repeated edits never drift. This was a conscious choice, not an oversight — see the Phase 6A
  scoping notes in `DATABASE_DESIGN.md` for the full reasoning and the caveat about
  single-timezone campus deployments.
- **Past events are excluded by default** (`include_past` query flag, off by default) rather than
  showing the full history. A calendar view is almost always about "what's coming up"; a toggle
  keeps the historical list one click away without making it the default view.

### Phase 6B: Meetings

- **`Meeting` reuses the exact `Announcement`/`Event` nullable-`department_id` scoping shape**
  (Phase 6A) rather than inventing a new one — a meeting is, structurally, just another
  college-or-department-scoped operational item with a schedule, and the spec doesn't distinguish
  "manage calendar" (events) from "manage meetings" as separate capabilities.
- **`MeetingAttendee` is a thin invite/attendance record, not a richer RSVP model.** No
  "declined"/"tentative" states — the plan only specified "attendee tracking (invited/attended)",
  so `attended` is a boolean rather than a multi-state enum, matching the "genuinely binary state
  gets a boolean, not an enum" judgment already used for `is_required`/`is_current`/`is_active`
  elsewhere in the schema.
- **Only the meeting's manager can mark attendance, never the attendee themselves.** This mirrors
  real meeting-minutes practice (the organizer takes roll), and keeps `MeetingAttendee` from
  needing its own Policy class — every attendee action is gated through `MeetingPolicy::update()`
  in the controller, the same "child action authorized via the parent's own policy" pattern
  `CompetencyEvaluatorController` already established in Phase 4B.
- **`MeetingActionItem` deliberately copies `StudentInterventionFollowup`'s column shape and
  Policy shape (Phase 3D) rather than being designed fresh.** Both are "an assignable, trackable
  work item hanging off a parent record," and the plan explicitly called for reusing that pattern
  instead of inventing a new one. The one structural difference — `MeetingActionItemPolicy` has
  no `view`/`viewAny` (visibility is entirely the parent `Meeting`'s), while
  `StudentInterventionFollowupPolicy` does have a `view()` — reflects that a meeting's action
  items are only ever seen embedded in the meeting's own Show page, with no standalone list
  elsewhere, whereas a student's follow-ups are surfaced on the student's own Progress page
  through a separate lookup path.
- **The assignee of an action item can update (including completing) and delete their own item,
  but cannot create new ones or manage anyone else's** — `MeetingActionItemPolicy::update()`
  checks `assigned_to === user.id` **or** meeting-manager status; `create()` only checks
  meeting-manager status. Only the meeting's manager decides what follow-up work exists; the
  assignee only controls their own progress on it. `delete()` intentionally equals `update()`
  (assignee included), matching `StudentInterventionFollowupPolicy::delete()` exactly rather than
  introducing a stricter rule with no precedent behind it.
- **A new `ActionItemStatus` enum, not a reuse of `InterventionStatus`.** The four states are
  identical, but `InterventionStatus` is named for the advising/intervention domain specifically;
  reusing it here would be a misleading name for a meeting action item. `ActionItemStatus` is
  named generically and is the enum `Task` (Phase 6C) is expected to reuse, since general Tasks
  need the same pending/in-progress/completed/cancelled lifecycle.
- **`Meeting` has a `Show` page; `Announcement`/`Event` do not.** The Phase 6A reasoning for
  skipping a `show` route was "the Index already displays everything." That reasoning doesn't
  hold for Meetings — attendees and action items are genuine nested child records that need their
  own management surface, not something an Index row can display inline. This is the same
  distinction already drawn between simple resources (Departments, Programs — no `show`) and
  resources with real child data (Graduation Candidates, Faculty Profiles — `show` combines
  several child-record cards).

### Phase 6C: Tasks

- **`Task` has no permission gate at all — no `tasks.manage`/`tasks.view` in the seeder, and
  `TaskPolicy::viewAny()`/`create()` simply return `true`.** Every other resource in the app is
  gated by a spatie permission, but Task isn't part of the spec's permission matrix — it's a
  generic personal-productivity utility ("assignable to-dos"), not an operations-management
  capability the spec assigns per role. The nav entry mirrors this: "Tasks" has no `permission`
  field, the same as "Dashboard" and "Data Import."
- **Any authenticated user, including Faculty, can create a task and assign it to anyone** — no
  department restriction on who can be assigned, unlike `CompetencyEvaluatorRequest`'s
  same-department requirement (Phase 4B). A cross-department ask ("can you help review this")
  is a legitimate use of a general to-do tool, so restricting assignment would cut against the
  feature's purpose.
- **`assigned_to` defaults to the creator when omitted**, rather than allowing a task with no
  responsible party. A task nobody owns isn't useful; defaulting to "my own to-do" matches how
  most task lists work when no explicit delegate is picked.
- **Field-level restriction on update, mirroring `FacultyProfileRequest` (Phase 5A) rather than
  `MeetingActionItemRequest` (Phase 6B).** `MeetingActionItemRequest` never needed this split
  because assignees only ever interact with action items through the inline status control — no
  full edit form was ever reachable by them. `Task` **does** have a full top-level Edit page
  (it's a standalone resource, not nested under a parent like `Meeting`), so without an explicit
  field restriction, a plain assignee reaching `/tasks/{id}/edit` could submit a title/reassignment
  change alongside a status change. `TaskRequest::rules()` returns a status/notes-only rule set
  when the actor is neither the creator nor an Admin, so any other submitted field is silently
  dropped by `validated()`, not merely hidden in the UI.
- **The full Edit page 403s outright for a plain assignee** (checked explicitly in
  `TaskController::edit()`), rather than rendering a stripped-down version of the same form. Since
  their only legitimate action (status/notes) already has a dedicated inline control on the Index
  list, there's no reason to let them reach a form most of whose fields would silently reject
  their input.
- **`delete()` is creator-or-Admin only — deliberately not `update()` reused, unlike
  `MeetingActionItemPolicy` (Phase 6B) where delete equals update.** A meeting's action items
  exist within a manager-governed parent record where the assignee *is* the one implementing that
  specific item, so letting them delete their own makes sense. A `Task` has no such managing
  parent — the creator is the only one accountable for whether it should exist at all, so deletion
  is narrower than status updates on purpose.
- **`Task::scopeVisibleTo()` is the first creator-or-assignee scope in the schema** — every prior
  model's `scopeVisibleTo()` was department-based (Admin/Dean unrestricted, Department Head own
  department, etc.). Task has no department concept, so Admin sees everything (oversight, same
  as always) and everyone else sees only what involves them personally.

### Phase 6D: Internal Requests

- **`request_histories` is written entirely by `InternalRequest` model events, never by
  controller code.** `static::created()` logs the initial `null → pending` row; `static::updating()`
  logs every subsequent transition whenever `isDirty('status')`. This is the exact pattern
  `Student` (Phase 2C) already established for `student_status_histories`, deliberately reused
  rather than having `InternalRequestController::review()`/`cancel()` each remember to write a
  history row by hand — a forgotten manual write would silently break the audit trail, while a
  model-event write cannot be skipped.
- **`department_id` is copied from the requester at submission time**, the same "snapshot a
  direct column rather than join through a relationship" choice used everywhere else a
  department-scoped `scopeVisibleTo()` needs to stay a simple, indexable `where()`.
- **A brand new `InternalRequestStatus` enum, not `ActionItemStatus` reused a second time.**
  Both have a `pending` case, which makes the reuse tempting, but the two enums represent
  different kinds of state machine: `ActionItemStatus` tracks work being done
  (pending → in-progress → completed), while `InternalRequestStatus` tracks a decision being made
  (pending → approved/rejected, or withdrawn). Collapsing them would make `MeetingActionItem`'s
  "completed" and `InternalRequest`'s "approved" look like the same concept when they aren't —
  the opposite of why `ActionItemStatus` was made generic in the first place (Phase 6B).
- **`InternalRequestPolicy::review()` explicitly forbids self-review**, checked before the
  role/department logic: `if ($request->requester_id === $user->id) return false;` — even for an
  Admin reviewing their own submitted request, or a Department Head reviewing their own. Every
  other Phase 6 resource lets Admin/the department authority act on anything in scope
  unconditionally; a request is the first resource in the app where "the actor" and "the record's
  natural approver" can be the literal same person, so this is the first place that guard was
  actually needed.
- **Visibility is a genuine four-tier split, not the three-tier department-broadcast shape used
  everywhere else in Phase 6.** Admin/Dean see everything (oversight, unchanged). Department Head
  sees their whole department's requests, because reviewing them is their job. But Faculty see
  only requests **they personally submitted** — not their colleagues' — unlike
  Announcement/Event/Meeting, where Faculty see all college-wide *and* department content because
  that content is meant to be seen by everyone it's addressed to. A leave request is addressed to
  a reviewer, not to the department at large, so the broadcast-visibility shape would leak
  personal information it was never designed to leak.
- **No edit page.** A `pending` request can only be cancelled (withdrawn) by its requester, not
  edited — the same "you don't edit a submitted form, you withdraw and refile" reasoning that
  kept `Announcement`/`Event` (Phase 6A) from needing a `show` route, applied here to skip an
  `edit` route instead. This also sidesteps a real question a mutable request would raise (does
  editing a pending request need its own history entry? should a reviewer be notified their
  in-progress review target just changed?) that a cancel-and-resubmit flow never has to answer.
- **`InternalRequest` has no `deleted_at`.** Cancellation already covers "the requester no longer
  wants this to be actioned," and unlike a soft-deleted row, a cancelled request stays visible
  (with its full history) as a real record of what was asked and withdrawn — closer to how
  `GraduationCandidate.status` can reach a terminal state without ever being deleted (Phase 4)
  than to a document that gets removed from storage (Phase 5C).

### Phase 6E: Document Repository

- **`document_categories` is a real Admin-managed table, not a PHP enum**, breaking from
  `StudentDocument`/`FacultyDocument`'s category-enum precedent (Phase 2D/5C). Those enums list a
  small, genuinely fixed set of document *kinds* tied to a specific person (diploma, transcript,
  license). A general document repository's categories are organizational labels
  ("Policies," "Forms," "Meeting Minutes") that vary by institution and should be addable by an
  Admin without a deployment — the same "small fixed set → enum, open-ended/institution-varying →
  configurable" judgment call, just landing on the opposite side this time because the *nature*
  of what's being categorized is different.
- **`DocumentCategory` management is Administrator-only**, checked via `hasRole(Administrator)`
  in addition to `operations.manage` — the same double-check `GraduationRequirementTemplatePolicy`
  (Phase 4A) uses. A Department Head can manage documents in their own department but shouldn't
  be able to add new categories to the shared, college-wide taxonomy every department draws from.
- **No `current_version_id` column on `documents`.** The "current" version is always just
  `MAX(version_number)` for that document, retrieved via `latestOfMany()` — storing a redundant
  pointer would be one more place a bug could let "current" drift from "actually latest."
- **A document's first version is uploaded in the same request that creates the document** — one
  combined form (title/description/category/audience + file), not a two-step "create the shell,
  then upload a file" flow. A `Document` with zero versions would be a browsable-but-undownloadable
  dead end; making the first upload mandatory at creation means that state can never exist.
- **The last remaining version cannot be deleted** (checked in `DocumentVersionController::destroy()`,
  not the Policy — it's a data-shape rule, not an authorization rule). If nothing is left worth
  keeping, deleting the whole `Document` is the correct action; a document that exists but has no
  downloadable content isn't a state worth allowing.
- **`DocumentVersionPolicy` has no `view`.** Whether a version can be downloaded is entirely
  gated by the parent `Document`'s own `view()` ability, checked directly in
  `DocumentVersionController::download()` — the same "child visibility is the parent's alone"
  pattern already used for `FacultyEducation`/`FacultyCredential` (Phase 5B) and
  `MeetingActionItem` (Phase 6B).
- **File storage and download follow `FacultyDocument`'s pattern exactly**: private
  `Storage::disk('local')`, a real filename preserved for the download response, never a public
  URL. This is now the fourth file-bearing resource in the app (`StudentDocument`,
  `FacultyDocument`, now `DocumentVersion`) using the identical storage approach — deliberately
  not varied per-resource.

### Phase 6F: Notifications and Phase 6 Wrap-up

- **The default Laravel notifications table needed no extension.** Earlier planning language
  ("notifications — Laravel's built-in notifications table, extended") anticipated needing extra
  columns; in practice, every notification's contextual detail fits in the standard `data` JSON
  column (`title`, `message`, `url`), so the table was built exactly as the framework ships it.
  This is a deliberate, documented course-correction rather than an oversight: the "extended"
  note was speculative planning written before any notification type existed, and turned out
  unnecessary once the four actual types were built.
- **Database channel only — no mail.** The plan's four named triggers (new announcement, meeting
  invitation, task assignment, request status change) describe in-app notifications; nothing in
  the spec calls for email delivery, and adding a mail channel now would be speculative
  infrastructure for a requirement that was never made.
- **Notifications are sent directly from the controller action that causes them**, not from a
  model event (unlike `RequestHistory`, Phase 6D). An audit trail must be structurally impossible
  to skip; a notification is a courtesy to a human and naturally belongs beside the code that
  already knows the full context of what just happened (who reviewed it, what the remarks were) —
  putting it in a model hook would mean re-deriving that context from a dirty-attribute diff for
  no benefit.
- **Announcement notifications go to every active user in the audience, computed at post time,
  not to users who happen to view the announcement later.** A department that gains a member
  after the announcement was posted doesn't retroactively get notified — this matches how the
  spec frames it ("new announcement" is a point-in-time event) and avoids the complexity of
  tracking "which users have been notified of which still-current announcements."
  `ActiveStatus::Active` users only — a disabled account isn't a real notification recipient.
  This is the same one-shot semantics `assertSentTo`/`Notification::fake()` verify in the tests:
  the notification fires once, at creation, to the audience as it existed then.
- **Task assignment notifies on both initial creation and reassignment, but never on
  self-assignment.** The plan names "task assignment" as a single trigger; reassigning a task via
  edit is the same event happening again, not a new named trigger, so both paths were wired
  without stretching the plan's scope.
- **The shared bell-summary prop is named `notificationCenter`, not `notifications`.**
  `Notifications/Index.tsx` already needed a page prop named `notifications` for its own
  paginated list (the natural name for that page). Since Inertia merges shared and page-specific
  props under one object, using the same key for both would let the page-specific value silently
  overwrite the shared one — which is exactly what happened during development (see below) before
  the shared prop was renamed.
- **Mark-as-read is chained through `onFinish`, not fired-and-forgotten before navigating.** An
  early version called `router.patch(...)` (mark read) immediately followed by `router.visit(...)`
  (navigate to the notification's target) with no dependency between them; the navigation's own
  request cancelled the in-flight PATCH (`net::ERR_ABORTED`), so the notification was never
  actually marked read despite looking like it worked in the UI. Caught during live browser
  verification (the unread badge didn't clear after clicking), not by the automated test suite —
  the Pest tests exercise the mark-read endpoint directly and had no way to observe a client-side
  request race. Fixed by navigating inside the PATCH's `onFinish` callback instead.

### Phase 7A: Research

- **Department Head is view-only (👁, own dept.), not manage-capable — a deliberate departure
  from Phase 6's pattern.** Phase 6 granted `operations.manage` to Department Head fairly
  liberally, largely a free design choice under ambiguous spec guidance. Phase 7's spec row
  ("Submit research/extension records") is explicit: `✅ | 👁 | 👁 (own dept.) | 🟡 (own) | 🅿7`
  — Department Head gets only view, Faculty gets manage-own. That explicit row is honored
  literally rather than exercised as free design judgment: `research-extension.manage` is
  Administrator-only in the seeder, and Faculty's create/manage-own ability comes entirely from
  `ResearchProjectPolicy` (blanket `create()` for any Faculty; `update()`/`delete()` gated on
  project leadership), never from a role-level `.manage` grant.
- **New `research-extension.manage`/`research-extension.view` permission pair, reused (not
  re-minted) for Extension in Phase 7B.** The spec gives Research and Extension an identical
  permission row, so one pair covers both — the same "permission reuse over proliferation"
  discipline applied throughout the schema, just deliberately anticipating the next sub-phase
  instead of only looking backward.
- **New `ResearchProjectStatus` enum, not a reuse of `InternalRequestStatus` or
  `ActionItemStatus`.** All three are shape-similar (a handful of lifecycle states), but the
  meanings differ: `InternalRequestStatus` (pending/approved/rejected/cancelled) is a decision
  workflow, `ActionItemStatus` (pending/in_progress/completed/cancelled) is a trackable work
  item, and `ResearchProjectStatus` (proposed/ongoing/completed/cancelled) is a project
  lifecycle — reusing either existing enum would borrow states that don't fit ("approved" makes
  no sense for a research project; "in_progress" doesn't capture "proposed but not yet started").
  Minting a new enum here is the same judgment already applied when `ActionItemStatus` was kept
  separate from `InterventionStatus` in Phase 6B despite looking similar on the surface.
- **`research_members.is_lead` is a boolean, not a `ResearchMemberRole` enum** — project
  leadership is a genuinely binary state, the same "binary state → boolean, not enum" reasoning
  already applied to `meeting_attendees.attended` (Phase 6B).
- **`research_outputs.type` and `research_projects.funding_source` are free strings, not
  enums** — output types and funding sources vary by institution and aren't a fixed, enumerable
  set, the same "open-ended/institution-varying → free string, not enum" reasoning already
  applied to `internal_requests.type` and `faculty_trainings.training_type`.
- **No numeric budget/funding-amount column on `research_projects`.** Not explicitly required by
  the spec; `funding_source` records where the money comes from, and adding an amount field with
  no stated reporting requirement behind it would be scope creep. Computed-on-demand,
  persisted-only-where-a-workflow-needs-it — the same philosophy already applied elsewhere in the
  schema (e.g. progress/deficiency computation in Phase 3A).
- **`department_id` on `research_projects` is required, not nullable-meaning-college-wide.**
  Unlike `announcements`/`events`/`meetings`/`documents` (Phase 6), a research project is always
  someone's own department's work — there's no "entire college" broadcast case to model, so the
  nullable-department pattern doesn't apply here.
- **A new project's creator is auto-added as its first `research_members` row with
  `is_lead = true`, inside the same DB transaction as project creation.** This is what lets a
  Faculty member's brand-new project immediately satisfy `ResearchProjectPolicy::update()`'s
  lead-membership check — without this, a Faculty member could create a project they then
  couldn't edit, since `research-extension.manage` is Administrator-only.
- **`ResearchMember`/`ResearchOutput` have no view/viewAny ability of their own; `create()`/
  `delete()` both delegate to `ResearchProjectPolicy::update()` on the parent project.** The same
  "child action authorized entirely through the parent's own policy" pattern already established
  by `CompetencyEvaluator` (Phase 4B) and `MeetingAttendee`/`MeetingActionItem` (Phase 6B) — a
  member or output is only ever seen embedded in the project's own Show page.
- **The `class_schedules.room` free-text-to-FK backfill (committed to in `DATABASE_DESIGN.md`
  when that column was introduced in Phase 2) is deferred to Sub-phase 7C (Facilities), not done
  here.** Research doesn't touch facilities at all; the backfill belongs with the sub-phase that
  actually introduces the `facilities` table it points to.

### Phase 7B: Extension

- **`ExtensionProject` is a deliberate structural parallel of `ResearchProject` (Phase 7A), not
  a fresh design.** Same required `department_id`, same lead-member ownership model
  (`ExtensionProjectPolicy` reuses `ResearchProjectPolicy`'s exact shape), same
  `research-extension.manage`/`.view` permission pair, same auto-lead-on-create transaction, same
  field-forcing FormRequest pattern. The spec treats Research and Extension as the same kind of
  capability ("Submit research/extension records," one permission row for both), so the
  implementation treats them as the same kind of module.
- **New `ExtensionProjectStatus` enum, kept separate from `ResearchProjectStatus` despite
  identical case values.** This is the same judgment already applied when `ActionItemStatus` was
  kept separate from `InterventionStatus` in Phase 6B, and when `ResearchProjectStatus` itself
  was kept separate from `InternalRequestStatus`/`ActionItemStatus` in Phase 7A: a status enum is
  named for a specific domain, and reusing one domain's enum for another — even with matching
  states — is a misleading name on the reusing side.
- **Extension gets two child record types (`activities`, `beneficiaries`) where Research got
  one (`outputs`) — sized to match the spec's own description, not padded out.** The spec
  explicitly lists "...activities conducted, and beneficiaries reached" as two distinct tracked
  things for Extension, unlike Research's single "outputs" concept.
- **`extension_beneficiaries` is a flat child of the project, not of a specific activity.**
  Modeling per-activity beneficiary attribution (project → activity → beneficiary) would be the
  schema's first three-level parent chain — no other module in this app nests two levels deep
  under a single parent (every existing child-of-child, like `MeetingActionItem` or
  `ResearchOutput`, is one level under its own direct parent, not a grandchild). The spec doesn't
  require per-activity attribution, so the simpler, precedented flat shape was kept.
- **`extension_activities.activity_type`/`extension_beneficiaries.beneficiary_type` are free
  strings, not enums** — the same "open-ended/institution-varying → free string" reasoning
  already applied to `research_outputs.type` (Phase 7A) and `internal_requests.type` (Phase 6D).
- **`extension_beneficiaries.count` is nullable** — not every beneficiary entry has a meaningful
  headcount (an LGU partnership vs. "38 farmer-households reached"), so it's left optional rather
  than defaulted to a value that would misrepresent "unknown" as a real count.
- **No new permission** — `research-extension.manage`/`.view` (minted in Phase 7A) is reused
  as-is, exactly as anticipated when that pair was named generically instead of
  `research.manage`/`research.view`.

### Phase 7C: Facilities

- **`Facility` reuses `operations.manage`/`operations.view`, not a new permission pair.** The
  spec has no facilities-specific permission row — unlike Research/Extension's explicit 🅿7 row
  that had to be honored literally, facilities authorization is a free design choice, and the
  choice made here is the same one Phase 6 made for Announcements/Events/Documents: a facility is
  an administrative/operational asset (Admin manages any, Department Head manages their own
  department's), not a personally-owned artifact like a Research project.
- **`facilities.department_id` is nullable (shared/college-wide vs. department-owned), reusing
  the Phase 6 Announcement/Event/Meeting/Document shape rather than Research/Extension's
  always-owned shape.** This is a genuine difference in kind, not an arbitrary choice: a research
  project always belongs to one researcher's department, but a facility can legitimately be
  either department-specific (a department's own lab) or shared (a college-wide lecture hall).
- **`facilities.type` stays a free string despite the spec naming four examples** (labs, farms,
  greenhouses, field locations) — real institutions have more location kinds than those four, and
  this schema's own backfill needed a fifth ("Classroom") to represent ordinary lecture rooms.
  Treating the spec's four examples as an exhaustive enum would have made the backfill impossible
  without inventing a type that doesn't fit the named list.
- **`facilities.created_by` is nullable — the only nullable `created_by` anywhere in this
  schema.** Every other resource's `created_by` is a real human action; a backfilled facility
  (created by a data migration, not a person) has no true creator. Forcing an attribution to
  whichever Administrator happened to run the migration would fabricate history; nullable is the
  honest choice.
- **The `class_schedules.room` → `facility_id` backfill is a single migration**, not split across
  a "add column" step and a separate "backfill data" step, because the two are inseparable here:
  the whole point of adding the FK is to immediately stop relying on the free-text column, so
  leaving both columns live even briefly would just create a second source of truth to keep in
  sync. The migration adds `facility_id`, creates one `Facility` per distinct historical `room`
  value (type `Classroom`, department `null`, creator `null`), repoints every matching schedule,
  then drops `room` — all in one `up()`.
- **Every historical `room` value becomes a `Classroom`-type facility, not something more
  specific**, because the backfill has no way to know whether "Room 101" was actually a lab, a
  farm plot, or a lecture hall — free text carries no structured type information. Staff can
  re-type a backfilled facility to something more specific via the ordinary Edit form after the
  fact; the migration's job is data preservation, not classification.
- **No `Show` page for `Facility`** — it has no child records in this sub-phase (equipment,
  Phase 7D, will reference facilities but doesn't live *under* them the way Meeting attendees or
  Research outputs do), so it follows the "simple lookup resource" pattern
  (Announcement/Event/DocumentCategory: Index says everything) rather than the "resource with
  genuine nested children gets a Show page" pattern.

### Phase 7D: Equipment

- **No `accountabilities` table, despite the original planned-schema list naming one.** "Who is
  accountable for what equipment right now" is a live derivation —
  `EquipmentBorrowing::whereDoesntHave('return')` — not a fact requiring its own storage.
  Persisting a parallel table would mean updating it in lockstep with every borrow/return, a
  second source of truth for data `equipment_borrowings`/`equipment_returns` already fully
  capture. This is the exact "computed on demand, persisted only where a workflow needs it"
  philosophy already applied to `FacultyWorkloadService` (Phase 5D — explicitly "no
  faculty_workloads table") and progress computation (Phase 3A). The accountability *report*
  still exists as a real page (`Equipment/Accountability.tsx`) — only the redundant storage was
  cut.
- **`equipment_borrowings` and `equipment_returns` stay two separate tables (not one row with a
  nullable `returned_at`), honoring the spec's literal naming.** This is the same judgment
  already exercised for Research/Extension's explicit permission row (Phase 7A) — when the spec
  names something specific, that's followed literally rather than treated as a stylistic
  suggestion. Practically, it also reads better as an audit trail: a borrowing row is written
  once and never mutated; a return is a distinct, separately-attributable event.
  `equipment_maintenance`, by contrast, stays a single table with nullable `completed_at` because
  the spec's list names it as one table, not two — the two decisions aren't inconsistent, they
  both follow the same "match the spec's literal schema where it's explicit" rule.
- **`equipment.status` is a persisted enum, unlike the deliberately-uncomputed
  accountability.** This looks like it contradicts the "compute on demand" philosophy above, but
  the two are different in kind: accountability is an *aggregate query* over history (which
  borrowings lack a return), while `status` is a *simple state machine* directly driven by one
  action at a time (borrow → Borrowed, return → Available, maintenance start → UnderMaintenance,
  maintenance complete → Available) — the same shape as `Task`/`MeetingActionItem.status`, both
  persisted columns updated by their own controllers. "Compute on demand" applies to expensive
  aggregates, not to ordinary workflow state.
- **`equipment.department_id` (ownership) and `equipment.facility_id` (current location) are
  independent nullable fields, not one derived from the other.** A department's equipment
  doesn't change ownership just because it's stored in a shared facility, so collapsing the two
  into a single field (or deriving department from the facility's own `department_id`) would
  conflate two genuinely different facts.
- **No delete/destroy action for borrowings, returns, or maintenance records.** These are an
  accountability trail by design — once written, they stay written. Only `Equipment` itself gets
  full CRUD including soft delete, mirroring the "some records are archived, not deleted" spec
  capability ("Archive and restore records") rather than treating history as freely editable.
- **`equipment_maintenance.performed_by` is a free string, not a `users` foreign key** — real
  maintenance is often done by an external vendor or non-system staff, not necessarily anyone
  with a CA-APOMS login. Forcing a `users` FK would make it impossible to record who actually did
  the work in the common case.
- **No equipment-specific permission, same as Facilities (Phase 7C)** — the spec has no row for
  it, so `Equipment` reuses `operations.manage`/`operations.view` rather than minting a new pair,
  following the identical free-design-judgment precedent Phase 6 and Phase 7C already
  established.

**Phase 7 (Agriculture Support Modules) is now fully complete.**

### Phase 8A: Role-Based Dashboards

- **No new tables, no snapshotting — this is the payoff of "compute on demand" being applied
  consistently since Phase 3.** Every dashboard number is a live aggregate query. This is the same
  discipline already applied to `FacultyWorkloadService` (Phase 5D) and the deliberately-absent
  `accountabilities` table (Phase 7D) — extended here to the module whose entire job is
  summarizing everything else.
- **Charts are current-state distributions, not trend lines, and that was a decision, not an
  oversight.** Back in Phase 3, `ASSUMPTIONS.md` explicitly reserved "a GWA trend line, a
  completion-rate dashboard" as the future trigger for a `student_progress_snapshots` table —
  i.e. Phase 8 work, but only the part of Phase 8 that needs point-in-time history. This
  sub-phase's charts (status breakdowns, pipeline funnels, at-risk-by-department) don't need
  history — they're answering "what does things look like right now," not "how did we get here."
  So the snapshot table stays unbuilt. If a future request specifically wants a trend-over-time
  view, that is the moment to revisit this, not before.
- **Dashboard content is entirely role-conditional in the controller, not permission-gated at the
  route.** `/dashboard` has never required a specific permission (every role needs *a* dashboard);
  Phase 8A's authorization is just "query different things for different roles," reusing each
  model's existing `scopeVisibleTo()` or a direct `department_id`/`adviser_id`/`assigned_to`
  filter — the same primitives every other module's scoping already relies on. No new
  authorization concept was introduced.
- **Faculty's dashboard has no charts, only stat cards.** A single faculty member's own data
  (a handful of sections, advisees, tasks) is too small a sample for a distribution chart to say
  anything a stat card doesn't already say faster. Admin/Dean/Department-Head dashboards, which
  aggregate across many students, are where a chart actually earns its place over a number.
- **`react-chartjs-2` / Chart.js is added in this sub-phase specifically**, matching the
  Technology Stack table's existing note ("Added in Phase 8 (Analytics)") — not earlier, since
  nothing before this needed a chart.

### Phase 8B: Reports

- **No report-caching table — same "compute on demand" discipline as every prior sub-phase.**
  `ReportService::generate()` is the single source of truth for a report's data; the Inertia
  preview and the PDF/Excel download both call it directly, on every request, so the two can never
  disagree with each other. A caching layer would only be worth adding if report generation became
  measurably slow at real data volumes — not a concern at department scale.
- **Each report is deliberately either an aggregate summary or a detail listing — a per-report
  choice, not a fixed template.** Enrollment Summary and Academic Performance group and count;
  At-Risk & Progress Summary, Faculty Workload Summary, and Graduation Pipeline Summary list one
  row per record. Reports are meant to be printed/filed/acted on, so row-level detail is often more
  useful than a Dashboard-style (Phase 8A) aggregate-only view — that's the intended division of
  labor between the two sub-phases: Dashboard answers "what's the current state," Reports answer
  "give me the underlying rows."
- **Academic Performance only counts `Finalized` or `Locked` grades**, excluding `Draft`/
  `Submitted`/`Reviewed`. A report is meant to reflect settled, official academic performance —
  counting in-progress encoding would make the same report produce different totals depending on
  how far through the grading workflow instructors happened to be on the day it was generated.
- **Graduation Pipeline Summary (this sub-phase) and the Phase 4D `GraduationReportController::
  batch()` PDF are complementary, not duplicates.** The Phase 4D report is a ceremony list — only
  `Approved`/`Graduated` candidates, formatted for a printed program. This sub-phase's report is
  oversight-facing — every pipeline status (`Pending`, `Under Review`, `Flagged`, `Approved`,
  `Graduated`, etc.) for a selected term, so an Admin/Dean/Department Head can see where every
  candidate currently sits, not just the ones who already cleared.
- **One generic `ReportExport` class and one generic `pdf/report.blade.php` template, reused
  across all 5 report types**, rather than five near-identical Export classes and five
  near-identical Blade files. Every report reduces to the same `{headings, rows}` shape by the
  time it reaches export, so a single parameterized implementation is a straightforward
  application of the generic-shared-implementation discipline already used for shared enums and
  permission-scoping helpers elsewhere in the project.
- **`reports.view` is one permission; college-vs-department scope is enforced by query, not by a
  second permission tier.** Matches the explicit-spec-permission-row-honored-literally precedent
  from Phase 7A/7C/7D: the spec's row (Admin ✅ college-level, Dean ✅ college-level, Department
  Head 🟡 department-level, Faculty ⛔) is satisfied with one permission plus
  `ReportService::departmentIdFor()` scoping — no `reports.view.college` / `reports.view.department`
  split.
- **The Graduation Pipeline report filters by `semester_id` only, not a separate
  `academic_year_id` filter.** A `Semester` already belongs to exactly one `AcademicYear`, so a
  second filter could only ever be picked consistently with the first — offering both would just
  let a user pick a mismatched pair. `ReportService::graduationPipeline()` derives the academic
  year internally from the selected semester.

### Phase 8C: Backup and Restore

- **No `backups` table — the filesystem is the source of truth.** `BackupService::list()` reads
  `storage/app/private/backups/` directly on every request rather than tracking rows in a
  database table. This mirrors the "compute on demand" discipline used everywhere else in Phase
  8, and sidesteps a real consistency risk a tracking table would introduce: if a backup file were
  ever deleted or moved outside the app (manually, by an ops script, etc.), a database row would
  silently go stale and claim a backup exists that doesn't. Reading the directory can never lie.
- **Backup/restore actions are logged via the existing `spatie/laravel-activitylog` package
  (`log_name = "backups"`), not a dedicated table.** `AuditLogController` (Phase 1) already renders
  any `Activity` record generically regardless of whether it has a subject model, so
  `activity('backups')->causedBy($user)->log(...)` for both success and failure gives Admin a full
  audit trail — including failed attempts, since a failed backup or a failed restore is exactly the
  kind of event an Admin needs visibility into — for free, with zero new schema.
- **`backups.manage` is Admin-only, with no entry in `ROLE_PERMISSIONS` for any other role** —
  mirrors the `users.manage` precedent (Phase 1): a permission that exists in the seeder's
  `PERMISSIONS` list (so Administrator receives it via `syncPermissions(self::PERMISSIONS)`) but is
  deliberately absent from every other role's grant array, rather than being explicitly denied.
  This is a stricter posture than Phase 8B's Reports (Admin/Dean/Department Head), matching the
  spec's own permission row: "Perform backup and restore" is ✅ for Admin and ⛔ for every other
  role, with no partial/department-scoped tier at all.
- **Real bug found and fixed during live browser verification, worth recording in detail because
  it is a genuine environment gotcha, not an application logic bug**: `BackupService::create()`
  consistently failed with `mysqldump.exe: Got error: 2004: "Can't create TCP/IP socket (10106)"`
  — but only when invoked through `php artisan serve`. The exact same `Process::run()` call
  succeeded reliably via `php artisan tinker`. Root-caused by reading Symfony Process's
  `getDefaultEnv()`: it fully inherits the parent's environment only on the `cli`/`phpdbg`/`embed`
  SAPIs; `php artisan serve` runs under `cli-server`, which instead takes
  `array_intersect_ukey(getenv(), $_SERVER, 'strcasecmp')` — and on this Windows host, that
  intersection silently dropped a variable `mysqldump.exe`'s own Winsock stack needs to initialize,
  even though the exact same MySQL connection details worked fine for Laravel's own PDO connection
  in the same process. Confirmed via a temporary debug route that raw `proc_open()` with `env =
  null` (true, unfiltered inheritance) succeeded every time, while `Illuminate\Support\Facades\
  Process` (which always goes through Symfony's SAPI-dependent filtering) failed every time in the
  `cli-server` context. Fixed by having `BackupService::mysqlEnv()` explicitly pass the process's
  full `getenv()` into every `Process::env()` call, which pre-populates every key so Symfony's
  filtered `getDefaultEnv()` merge (`+=`, additive-only) never has anything left to drop. This is
  not a workaround specific to this machine — any Windows deployment that serves the app via `php
  artisan serve` (the documented local dev workflow, per `INSTALLATION.md`) would hit the same
  failure without this fix.

### Phase 8D: Hardening and Finalization

The closing sub-phase of the project: a performance audit, a security audit, a mass-assignment
audit, an accessibility pass on key pages, one final full regression run, and writing the four
documentation files scoped out of Phase 1 (see "Documentation Scoping" below) now that their
subject matter is real. All three audits were run as independent research passes across the full
75-controller, 70-model, 45-policy, 61-FormRequest codebase before any fix was applied, so the
fix list below is evidence-based rather than a guess at what "hardening" should mean.

**Fixed — security (2 real, exploitable gaps found)**:
- `ImportController::show()`/`errors()` had **no authorization check at all** — any authenticated
  user could read another department's import batches, including `errors()`'s raw per-row import
  data (student PII, grades). `index()` also listed every batch college-wide with no scoping.
  Fixed: `show()`/`errors()` now check the same per-type permission `store()` already checked
  (`students.import`, `courses.manage`, etc.); `index()` now filters to only the types the
  requesting user can access.
- `StudentRequest` never forced `department_id` to the authenticated user's own department for
  non-Administrators, unlike every sibling FormRequest with a `department_id` field
  (`AnnouncementRequest`, `DocumentRequest`, `EventRequest`, `EquipmentRequest`, etc.). Since
  Department Head holds `students.manage` and `StudentPolicy::create()` only checks that blanket
  permission (no department check), a Department Head could submit a crafted `department_id` and
  plant — or, via `update`, move — a student record into another department. Fixed with the same
  `prepareForValidation()` forcing pattern used everywhere else. The equivalent gap in
  `CourseRequest`/`ProgramRequest` was hardened the same way even though it's not currently
  exploitable (both permissions are Administrator-only today) — for consistency with the pattern
  and because permissions in this app have grown more permissive over phases before (e.g.
  Department Head's `advising.manage`, `enrollment.manage`), not less.
- Two LOW-severity, currently-inert consistency gaps were also closed: `CurriculumCourseController`
  now verifies a `curriculumCourse` actually belongs to the `curriculum` in the route (mirrors
  `EnrollmentCourseController`'s existing guard) before mutating it; `CurriculumPolicy::update()`/
  `delete()` now check department ownership the same way `CurriculumPolicy::view()` already did,
  rather than only checking the blanket `curricula.manage` permission.

**Fixed — N+1 queries (all 4 found, across two separate passes)**:
- `FacultyWorkloadService::summaryFor()` re-ran a full `sectionsFor()` query (itself several
  eager-loaded relations) once per faculty member — an M-query N+1 hit on every Faculty Workload
  dashboard load and every Faculty Workload report/export. Rewritten to batch one
  `FacultyAssignment::whereIn('faculty_id', ...)` query grouped in memory; `sectionsFor()` itself
  is untouched since the single-faculty detail view still needs its full per-section shape.
- `ReportService::facultyWorkload()` was missing `->with('department:id,name')` on the faculty
  query feeding into the summary above, causing one extra lazy `Department` lookup per faculty
  member on top of the M-query problem. One-line fix.
- `GraduationCandidateService::identifyEligibleStudents()` and `AtRiskController::index()` (via
  `ProgressAlertService::syncAlertsForScope()`) each ran `ProgressComputationService`'s checklist
  computation (multiple queries) plus a separate deficiency-count query **per student in scope**,
  on every visit to the "nominate a candidate" page and the Academic Progress page respectively.
  Originally left deliberately unfixed during this Phase 8D pass (see below for the reasoning at
  the time) and fixed in a later, dedicated pass: `ProgressComputationService::preloadForStudents()`
  now batches the three genuinely per-student-varying query sources — the default grading scale
  (identical for every student, memoized once), each distinct curriculum's course list (shared by
  every student on that curriculum, one query per distinct curriculum instead of one per student),
  and every student's own enrollment/grade history (one `whereIn`-batched query for the whole
  collection instead of one per student) — into a single call before either hot loop runs, plus
  `withCount()` for the deficiency count. Two real bugs surfaced during that fix and are worth
  recording: `Collection::merge()` re-indexes integer keys via its `array_merge()` backing, which
  silently corrupted a cache keyed by student ID (fixed by switching to `union()`, which preserves
  them); and the batch preload originally warmed the grading-scale cache unconditionally, which
  turned "zero students in scope" into a hard 500 via `GradingScale::default()`'s `firstOrFail()`
  instead of the harmless no-op it was before (fixed with an early return on an empty collection).
  `checklist()`'s own *result* is deliberately NOT memoized per student on top of this — an early
  version of the fix did, and it broke `syncDeficiencies()` being called a second time for the
  same student after a retake was recorded, since the second call silently returned the first
  call's stale rows. Only the three inputs that never change mid-request (grading scale,
  curriculum definition, one preloaded batch's own attempts) are cached.

At the time of the original Phase 8D pass, both were deliberately left alone rather than rewritten
mid-hardening-pass, since they sit inside already-shipped, already-tested business logic from
Phase 3/4 built under this project's explicit "compute on demand" philosophy, and batching across
a whole cohort was judged a real refactor, not a one-line fix — this is the same "leave it a
known, written-down limitation rather than a risky mid-hardening rewrite" call the project has
made before (e.g. Phase 3's explicit deferral of a `student_progress_snapshots` table until
something actually needs point-in-time history). The fix above was done later, as its own
dedicated pass with full regression coverage, rather than folded into Phase 8D.

**Accessibility pass** (Dashboard, a list page, a create form, a modal — chosen as one
representative page per UI pattern rather than an exhaustive sweep of all ~80 pages, given the
size of the codebase):
- Dashboard's Chart.js `<canvas>` elements had `role="img"` (Chart.js's default) but no
  `aria-label` — a screen reader announced an empty, unnamed image. Fixed by wrapping each chart
  in a `role="img"` container with a generated `aria-label` summarizing the underlying data (e.g.
  "Students by Status. Active: 24, On Leave: 4, ...") and marking the `<canvas>` itself
  `aria-hidden="true"` so assistive tech gets one coherent description instead of two.
- The shared `Pagination` component (used by every paginated list page in the app) had no
  `aria-current="page"` on the active page link and no accessible names distinguishing "1"/"2"/
  "Next »" beyond their visible text. Fixed once, centrally, so every list page benefits.
- `Students/Index.tsx`'s search input and two filter `<select>`s had no accessible name at all
  (placeholder text alone isn't reliably announced); its table `<th>` cells had no `scope="col"`.
  Both fixed on this page as the flagship/highest-traffic example of this pattern.
- `Students/Form.tsx`'s local `Field` wrapper rendered `<InputLabel>` next to its input but never
  wired `htmlFor`/`id` — all ~30 fields on the student registration/edit form were unlabeled for
  assistive tech, despite being visually labeled. `ClassSections/Form.tsx` had the identical bug
  (no `Field` wrapper, but the same missing-`htmlFor` pattern) across all 6 of its fields; the
  established, correct pattern elsewhere (`Departments/Form.tsx` and 15 other `Form.tsx` files)
  already pairs every `InputLabel htmlFor` with a matching input `id`. Grepping every `Form.tsx`
  for `InputLabel`-vs-`htmlFor` counts found these were the only two fully broken and one partly
  broken (`Courses/Form.tsx`, missing on its two checkbox-group "Prerequisites"/"Corequisites"
  labels specifically — fixed by switching those two to `<fieldset><legend>`, the correct pattern
  for a label describing a group of checkboxes rather than one input). All three fixed; the
  `Modal` component (Headless UI `Dialog`) was checked and already handles `role="dialog"`,
  `aria-modal`, and focus-on-open correctly out of the box — no fix needed there.

**Final regression**: 341 Pest tests passing (327 at the close of Phase 8B, +10 for Phase 8C,
+4 for Phase 8D's security regression tests — 3 for the import authorization fix, 1 for the
student department-forcing fix), Pint clean, `tsc --noEmit` clean, `npm run build` clean,
`migrate:fresh --seed` clean.

## Documentation Scoping

- `DEPLOYMENT.md`, `BACKUP_RESTORE.md`, `USER_GUIDE.md`, and `API_DOCUMENTATION.md` are
  **not** written in Phase 1. They depend on features that don't exist yet (there is nothing
  to deploy differently, nothing to back up beyond "it's a MySQL database," no user-facing
  workflow beyond login yet, and no API surface beyond Inertia's own page responses). Each is
  written once its subject matter is real, to avoid documentation that describes features
  that don't exist. `PROJECT_PLAN.md`, `DATABASE_DESIGN.md`, `ROLE_PERMISSIONS.md`,
  `ASSUMPTIONS.md`, `README.md`, and `INSTALLATION.md` are written now because they describe
  the plan and the Phase 1 foundation, which does exist.
- Sidebar navigation shows the **full module list** from the spec (Section 8) from Phase 1
  onward, per the spec's explicit UI requirement, even though most routes don't exist yet.
  Unbuilt modules link to nothing (not rendered as clickable) until their phase — see
  `PROJECT_PLAN.md` §5 — rather than 404ing, which would look like a bug rather than
  "not built yet."

## Development Environment

- Local development uses **XAMPP's bundled MariaDB 10.4**, connected to as a standard
  MySQL-protocol server (`DB_CONNECTION=mysql`). Nothing in the app is MariaDB-specific;
  it will run against real MySQL in production without changes.
- Queue driver is `database` in development (no Redis dependency for local dev); the code
  is queue-connection-agnostic so production can switch to Redis/SQS via `.env` alone.

## Post-Launch: Hybrid Online/LAN/Offline Sync

A full architecture plan exists for making CA-APOMS usable Online, over the office LAN (the
Administrator's PC as sync hub), and fully Offline, with a manual Update/Sync button reconciling
changes across instances without ever silently overwriting data. The plan and its full reasoning
live in `C:\Users\ACER\.claude\plans\quirky-popping-parnas.md` (a local Claude Code plan file, not
committed to this repo) — summarized here for anyone who doesn't have that file:

- **The framing that matters most**: Inertia.js cannot render a single page without a live
  connection to a running Laravel server — there is no client-side router or data layer to fall
  back on. So "fully offline" here does not mean an offline-capable frontend (no service worker,
  no IndexedDB) — it means two independent, already-functioning Laravel installations (the
  Administrator's local one, and a future cloud one) periodically exchanging incremental changes
  over HTTPS. That reframing is why this is a database/API-level addition, not a frontend rewrite.
- **Scope decisions already made**: only the Administrator's PC needs true standalone offline
  capability (it already has that today, running locally — LAN/Dean/Head/Faculty machines are
  just browsers, no local install of their own); no cloud deployment exists yet, so cloud-sync
  work will be proven against a second local instance standing in for one; and the engine is being
  built and fully proven against a small pilot table set (`Student`, `StudentEnrollment`,
  `EnrollmentCourse`, `StudentGrade`) before expanding to the other ~65 sync-candidate tables.
- **Phase 1 (Foundation) is complete**: additive `uuid`/`sync_version`/`origin_device_id` columns
  on the 4 pilot tables (all existing `SoftDeletes` tombstones reused, no new deletion-tracking
  columns needed — see `ROLE_PERMISSIONS.md`'s note on `StudentGrade` specifically), five new
  metadata tables (`devices`, `sync_changes`, `sync_checkpoints`, `sync_conflicts`, `sync_runs`),
  Sanctum published and wired to `User` (was a dormant composer dependency before this — no
  config, no migration, no trait), and `SyncChangeObserver` populating the `sync_changes` outbox
  on every real write to a pilot model. No API routes, no UI, no live data transfer yet — see
  `ROLE_PERMISSIONS.md` for the `sync.manage` permission this introduced.
- **`uuid` is nullable at the schema level**, not `NOT NULL` — `doctrine/dbal` isn't installed, so
  there's no `Schema::table(...)->change()` available to enforce it after backfilling existing
  rows. `SyncChangeObserver`'s `creating()` hook guarantees every new row gets one; the migrations
  backfill every pre-existing row in the same pass via `chunkById()`. This is an accepted tradeoff,
  not an oversight — adding `doctrine/dbal` as a new dependency for one column constraint wasn't
  judged worth it.
- **Phase 2 (one-way pull, complete)**: `routes/api.php` (registered in `bootstrap/app.php`'s
  `withRouting()` — the first API surface this app has ever had), Sanctum-gated (`auth:sanctum` +
  `permission:sync.manage`) `GET /api/sync/status` and `GET /api/sync/pull`. `SyncPullService`
  does both sides: `pendingChangesSince()` serves an ID-cursor-based incremental payload (one
  entry per distinct entity touched, current-state snapshot, not per intermediate write);
  `applyIncoming()` consumes one (idempotent — a version-guard means re-applying an already-seen
  or older change is a no-op); `pullFrom()` orchestrates a full pull against a configured remote
  URL/token, advances a `sync_checkpoints` row, and records a `sync_runs` entry. Device
  registration is a console command (`sync:register-device`) for now, not a UI — Device
  Management is Phase 4/6 scope.
  - **Known, deliberate gap**: pilot models carry foreign keys (`department_id`, `program_id`,
    `curriculum_id`, ...) into reference tables that aren't themselves synced yet. An incoming
    `Student` only resolves correctly if both instances already share the same reference-table
    IDs. Proven honestly, not hidden: the live two-instance test used two genuinely separate
    MySQL databases with real HTTP between two independently running `php artisan serve`
    processes, but deliberately seeded matching reference rows by explicit ID
    (`Department::forceCreate(['id' => 9001, ...])` on both sides) rather than pretending this
    works for two independently-seeded production instances. Real production use needs the
    reference tables synced too — Phase 6 (Expansion).
  - **Known, deliberate gap**: `applyIncoming()` writes are quiet (`saveQuietly()`), which
    correctly stops `SyncChangeObserver` from re-logging incoming data as a new local change, but
    also suppresses `StudentGrade`'s own `booted()` hook that writes `grade_change_logs`. A synced
    grade change doesn't get a local audit-trail entry yet. Laravel has no "suppress this one
    listener, keep the rest" primitive; doing this properly needs a flag-based opt-out on the
    observer. Left for Phase 3+, which needs finer-grained event control for push/conflict
    handling anyway.
  - Verified: 14 new tests (`SyncPullApiTest`, `SyncPullServiceTest` — auth/permission/shape,
    idempotent create/update/delete, HTTP-faked `pullFrom()`), full regression green, and a live
    two-instance proof: created a `Student` on one running instance, pulled it into a second,
    fully separate instance via real HTTP with the exact same `uuid`, then pulled again and
    confirmed zero duplication (the checkpoint correctly saw nothing new to fetch).
- **Phase 3 (push + three-way conflict detection, complete)**: `SyncPullService` was renamed to
  `SyncService` — it now serves both directions, since `applyIncoming()` is the single place either
  a pull-response or a push-request gets merged in, so the two directions can never disagree about
  what counts as a conflict. `POST /api/sync/push` (same `auth:sanctum` + `permission:sync.manage`
  gate) accepts a device's own pending changes and runs them through `applyIncoming()`; `pushTo()`
  is the client-side orchestration counterpart to Phase 2's `pullFrom()`, tracking its own outbound
  cursor via a `SyncCheckpoint` row keyed by `"{remote_target}:push"` — a separate row from the pull
  cursor, since one tracks what's been consumed and the other what's been sent.
  - **Three-way merge**: every `sync_changes` row now carries `changed_fields` (the business
    columns that specific write touched) and `base_version` (the entity's `sync_version`
    immediately before that write) — captured by `SyncChangeObserver`, which passes state between
    its `updating()`/`updated()` hooks via a `WeakMap`. That only works because `SyncChangeObserver`
    is bound as a singleton in `AppServiceProvider::register()` — `Model::observe()` registers
    observer methods as string listeners that Laravel's dispatcher otherwise re-resolves fresh from
    the container on every single event, which would silently hand `updating()` and `updated()` two
    different instances with two different empty `WeakMap`s. Safe as a singleton here because this
    app has no persistent worker (no Octane) — each HTTP request gets a fresh container regardless.
  - `pendingChangesSince()` collapses a run of changes per entity into one payload entry carrying
    the *earliest* `base_version` and the *union* of `changed_fields` across the run (from its
    `updated` rows only — a `created` row elsewhere in the same id-range doesn't null this out,
    since that create may just be this outbox's own genesis record for an entity the receiver
    already independently has, e.g. two instances seeded from the same baseline before any sync
    ran; what matters is the version immediately before the earliest *business* change in range).
  - `applyIncoming()` classifies each incoming change into one of four outcomes: **fast-forward**
    (local hasn't changed since the incoming change's `base_version`, or there's no base to check —
    apply the full snapshot, exactly Phase 2's behavior); **safe merge** (local diverged since base,
    but touched different fields than the incoming change — apply only the incoming change's
    fields, via `collect($snapshot)->only($remoteChangedFields)`, leaving local's own edits to its
    own fields untouched); **hard conflict** (the same field was touched on both sides, or the
    remote's `changed_fields` is unknowable, or the entity is a `StudentGrade` — grades never
    auto-merge even on non-overlapping fields, per the spec's principle that a grade must never be
    silently overwritten); **conflict on delete** (the remote deleted the row after local had
    already diverged past that point — recorded as a conflict with a `__remote_deleted__` marker in
    `conflicting_fields` rather than either silently deleting or silently reviving). A repeated
    conflicting change updates the existing `pending` `SyncConflict` row's snapshots rather than
    duplicating it. Divergence is detected by comparing against `base_version`, not by comparing raw
    `sync_version` numbers — once two sides can diverge, each increments its own counter
    independently, so two genuinely different states can carry the same (or a "higher" local)
    version number; only `base_version` reliably answers "has local changed since this specific
    remote write branched off."
  - **Known, deliberate gap, not solved here**: a double-apply of an already-merged or
    already-conflicted change re-runs the merge/conflict logic rather than cleanly short-circuiting
    to "skipped" the way a plain fast-forward does — safe (idempotent in effect, since it reapplies
    the same field values / refreshes the same conflict snapshot) but not as clean as true
    idempotency. Not solved because a real per-entity "have I already incorporated this exact write"
    check would need a vector-clock-style structure this pilot deliberately doesn't build.
  - Verified: 20 new tests (`SyncServiceTest`, `SyncPushApiTest` — safe merge, overlapping-field
    conflict, repeated-conflict dedup, grade-always-conflicts, delete-vs-edit conflict, push
    contract/auth/attribution), full regression green (420/420), and a live two-instance proof
    across two genuinely separate running instances with real HTTP and real separate MySQL
    databases: (1) concurrent edits to different `Student` fields on both sides auto-merged
    bidirectionally with zero conflicts, each side ending up with both edits; (2) concurrent edits
    to the *same* `Student` field recorded a conflict with correct `local_snapshot`/
    `remote_snapshot`/`conflicting_fields`, and the receiving side's data was left completely
    untouched — not silently overwritten; (3) concurrent edits to *different* `StudentGrade` fields
    (`grade` on one side, `status` on the other — which would auto-merge for any other pilot model)
    still recorded a conflict, confirming the grades-always-conflict rule holds even without field
    overlap.
- **Phase 4 (Sync Center UI, complete)**: a web-facing (session-auth, not Sanctum) admin dashboard at
  `/sync`, gated by `permission:sync.manage` exactly like Backups/Branding — `SyncCenterController`
  (`App\Http\Controllers\Admin`), no dedicated Policy. Three pages: `Sync/Index` (this instance's
  device identity, configured remotes with a manual "Sync Now" button, the device registry, and the
  last 5 runs), `Sync/History` (paginated `sync_runs`), `Sync/Conflicts` (paginated pending
  `sync_conflicts` with a two-column local/remote field diff — new UI, no existing precedent in this
  app to copy from — and Take Remote / Keep Local resolution buttons).
  - **New storage this phase adds**, both following established conventions rather than introducing
    a generic key-value settings table (none exists anywhere in this app): `devices.is_local`
    (boolean, marks which registered `Device` row IS this running instance — the identity
    `SyncService::pullFrom()`/`pushTo()` need; enforced "only one at a time" at the application
    layer, the same way `BrandingController` enforces "one `College` row", not via a schema
    constraint) and a new `sync_remotes` table (`name`, `base_url`, `token` — the Admin-configurable
    remote address storage that Phase 2's `pullFrom()`/`pushTo()` docblocks always said Phase 4
    would add, instead of those methods continuing to take raw arguments). `token` uses Eloquent's
    `encrypted` cast — never stored or displayed in plaintext; the edit form leaves it blank to keep
    the existing token unless a new one is typed.
  - **`SyncService::reconcile()`**: the "Sync Now" button's orchestration — calls `pullFrom()` then
    `pushTo()` against one `SyncRemote`, each independently try/caught so a one-way failure (e.g.
    the remote is offline) doesn't block attempting the other direction. Both directions' `SyncRun`
    rows are visible in History regardless of outcome.
  - **`SyncService::applyResolution()`**: 'take_remote' applies the conflict's `remote_snapshot`
    through a normal (non-`saveQuietly`) `save()` — deliberately not quiet, unlike
    `applyIncoming()`'s merge/fast-forward paths, so the resolution itself becomes a fresh, correctly
    `base_version`-ed local outbox entry that can propagate onward to other remotes on the next sync.
    'keep_local' mutates nothing. Both mark the `SyncConflict` `resolved` with `resolution`,
    `resolved_by`, `resolved_at`.
  - **Known, deliberate gap**: resolving a conflict is a LOCAL decision — it does not guarantee the
    same historical conflicting change can never be re-offered by a remote that re-sends an old,
    already-acknowledged batch (e.g. after a checkpoint reset), since there's no
    resolution-propagation message sent back to the remote saying "this was resolved, stop offering
    it." Out of scope for this pilot's single-remote-pair topology — the same class of scoping
    decision as the reference-table FK gap (Phase 2) and the double-apply idempotency gap (Phase 3).
  - Header status pill (`pendingSyncConflicts`, shared via `HandleInertiaRequests`, gated inside the
    closure on `sync.manage` so non-Admins always get `null` rather than the key being omitted)
    shows a red "N sync conflicts" pill linking to `/sync/conflicts` when any are pending, or a green
    "Sync OK" pill linking to `/sync` otherwise — visible to Admins on every page, not just the Sync
    Center itself.
  - Verified: 13 new tests (`SyncCenterTest` — permission gating on every route, remote CRUD
    including the blank-token-on-update behavior, set-local-device, `syncNow` via HTTP-faked
    pull+push, history/conflicts listing and status filtering, both resolution paths including
    asserting the entity is/isn't mutated, invalid-resolution rejection, and the shared prop's
    admin-only gating), full regression green (433/433), Pint and `tsc --noEmit` clean, and a manual
    browser walkthrough against the real dev server and database: viewed the overview with a live
    pending-conflict banner, resolved it via "Keep Local" and watched the banner disappear
    immediately; triggered "Sync Now" against a deliberately unreachable remote and confirmed both
    the pull and push attempts recorded `failed` `SyncRun` rows with the underlying cURL error
    message surfaced in History, rather than crashing or silently doing nothing; opened the Add
    Remote modal and confirmed all three fields render correctly.
- **Phase 5 (resilience, complete)**: no new sync logic — this phase audited the existing Phase 2-4
  machinery for what happens when a sync is genuinely interrupted, found one real gap, fixed it, and
  added regression coverage plus a live proof for the properties the design already claimed but
  never had a dedicated test for.
  - **Real bug found and fixed**: `SyncController::push()` called `SyncService::applyIncoming()`
    directly, unlike `pullFrom()`, which wraps the equivalent call in `DB::transaction()`. A push
    batch that failed partway through (a malformed change, a DB constraint violation on, say, a
    fifth entity in a twenty-entity batch) would leave the first four committed and the rest not —
    a silent, undetectable partial application, exactly the kind of thing principle #51 (never
    silently lose or corrupt data) is about. Fixed by wrapping it in `DB::transaction()` too, so a
    push batch now either lands completely or not at all, symmetric with pull. While in this method,
    also added `$device?->update(['last_sync_at' => now()])` for the pushing device — previously
    only pull/push *initiated* by this instance updated a device's `last_sync_at`; an incoming push
    from a remote never updated that remote's own device row, so the Sync Center's Devices table
    under-reported how recently a device had actually synced.
  - **Idempotent-retry property, now explicitly tested rather than assumed**: if a push/pull
    succeeds on the receiving side but the caller never sees the response (connection reset after
    the remote committed, a proxy timeout, the LAN cable coming loose mid-response), the caller's
    checkpoint never advances, so its next attempt resends the exact same batch. Because
    `applyIncoming()`'s version-guard already made re-application safe (Phase 2's design), this
    "lost response" scenario was already handled correctly — but had no test proving it end-to-end
    through the real `/api/sync/push` endpoint until now.
  - Verified: 6 new tests (`SyncResilienceTest` — a partially-failing batch rolls back atomically on
    both the pull-consuming and push-receiving paths, a failed `pullFrom()`/`pushTo()` leaves its
    checkpoint completely untouched so the retry re-fetches/re-sends the identical batch, resending
    an already-applied batch through both the service method directly and the real API endpoint is a
    safe no-op, and a pushing device's `last_sync_at` is now recorded), full regression green
    (439/439), and a live two-instance proof across two genuinely separate running instances,
    exercised through the actual Sync Center UI (not raw service calls): killed the remote instance's
    server entirely, clicked "Sync Now" on the live `/sync` page, and confirmed both the pull and
    push attempts failed cleanly with `failed` `SyncRun` rows and zero local data corruption; made a
    real edit on the surviving instance while the remote stayed down; restarted the remote; clicked
    "Sync Now" again and confirmed it succeeded, correctly delivered exactly the pending edit with no
    duplication (`SELECT COUNT(*)` for the synced row stayed at 1 throughout), and the Devices
    table's "Last Synced" column updated accurately; clicked "Sync Now" a third time with nothing
    pending and confirmed a clean, error-free no-op.
- **Phase 6 (expansion — reference-table sync + FK translation, complete)**: closed the reference-
  table gap documented since Phase 2 — the User explicitly scoped this phase to "expand the table
  set" over the other three candidate slices (Device Management UI, file/document sync, LAN
  deployment docs), which remain undesigned.
  - **10 tables joined the synced set**: `colleges`, `academic_years`, `year_levels`, `departments`,
    `programs`, `courses`, `curricula`, `semesters`, `sections`, `class_sections` — every reference
    table the 4 pilot tables transitively depend on (traced via a full FK audit of the pilot chain,
    not guessed). Same additive migration pattern as Phase 1 (`uuid`/`sync_version`/
    `origin_device_id` + backfill). `year_levels` also gained `SoftDeletes` (a `deleted_at` column) —
    it was the one synced-eligible model without it, and the sync engine's tombstone mechanism
    universally assumes `withTrashed()`/`trashed()` exist; a behavior no-op today since nothing
    currently deletes a `YearLevel`.
  - **The actual gap-closing work — FK-to-uuid translation**: adding tables to the synced set alone
    doesn't fix cross-instance correctness, since two independently-seeded instances still
    auto-increment their own, different IDs for the same conceptual row. `SyncService::FK_REFERENCES`
    declares every FK column (on both the 10 new tables *and* the original pilot tables — e.g.
    `student_enrollments.student_id`, `enrollment_courses.student_enrollment_id`,
    `student_grades.enrollment_course_id`, previously unresolved too) that points at another synced
    table. `snapshotFor()` now sends those columns' *referenced row's uuid* alongside the raw value
    (under a `_fk_uuids` key inside the snapshot); `resolveForeignKeys()` translates that back into
    whatever local ID the receiving instance actually gave that row, on every apply path (create,
    fast-forward, field-level merge, and conflict-resolution's `take_remote`). FKs into `users`
    (`encoded_by`, `adviser_id`, ...) are deliberately excluded — User accounts still aren't synced,
    out of scope here same as before.
  - **Dependency-order sorting**: `pendingChangesSince()` sorts its output by a fixed topological
    order (`SYNC_ORDER` — reference tables before their dependents) so that within one batch, a
    dependent entity's FK always resolves against a reference-table row appearing earlier in the
    *same* batch, regardless of which raw `sync_changes` id each happened to get.
  - **Deliberate failure mode**: if a referenced row genuinely can't be found locally (a dependency
    landed in a *later* page than its dependent — only possible at the 200-per-batch boundary, since
    `SYNC_ORDER` handles same-batch ordering), `resolveForeignKeys()` throws rather than silently
    skipping. Because this always runs inside a `DB::transaction()` (Phase 5), throwing rolls back
    the whole batch and leaves the checkpoint un-advanced, so the next sync attempt retries the exact
    same batch — consistent with Phase 5's "a clean failure + retry beats a silent skip" philosophy.
  - **Known, deliberate limitation — still not "seed two instances from scratch and expect them to
    agree"**: FK translation only works once the referenced row has already synced at least once.
    A row that predates a table joining the synced set (e.g. this app's own `College`, seeded by
    `CollegeDepartmentSeeder` long before Phase 6) has a backfilled `uuid` but *no outbox history* —
    Phase 1 never backfilled synthetic "created" entries for pre-existing rows either, so this isn't
    a new gap, just a newly-relevant one now that reference tables carry real pre-existing data. One
    edit brings a pre-existing row into the outbox for the first time (proven in the live proof
    below). A genuinely brand-new second instance — two independent `db:seed` runs sharing zero
    history — still needs to be bootstrapped via backup/restore (the existing `BackupService`) before
    incremental sync can do anything; sync heals *ongoing* divergence, it was never meant to invent a
    shared history that never existed. `College` also deserves a specific callout: it's a
    "single-college install" singleton by convention (see `BrandingController`'s docblock) — two
    independently-seeded instances would each mint their *own* `College` row with different uuids,
    and syncing would produce two rows, not a merge. Not solved here; the same backup/restore
    bootstrap avoids it in practice.
  - Verified: 7 new tests (`SyncReferenceTableTest` — FK translation for a single-column case
    (Department→College), a Student's four FKs translated at once, cross-pilot-table translation
    (StudentEnrollment→Student), the not-yet-synced-dependency throw, dependency-order sorting
    surviving an out-of-order raw id, and backward compatibility with pre-Phase-6 payloads that carry
    no `_fk_uuids` at all), full regression green (446/446 — including a pre-existing, unrelated
    flaky factory fixed along the way: `ClassSectionFactory`'s single-letter `section_label` had only
    26 possible values and collided under this suite's growing volume of factory calls; widened to
    three characters), and a live two-instance proof deliberately *not* using matching IDs this time:
    seeded Instance B with filler departments/programs first so its auto-increment counters were
    guaranteed to diverge from Instance A's, synced Instance A's pre-existing `College` over (after
    the one-time "touch to enter the outbox" step above), then created a brand-new Department →
    Program → Curriculum → YearLevel → Student chain on Instance A using ordinary auto-increment (no
    explicit ids at all) and pushed it — every single FK on Instance B's resulting rows correctly
    pointed at Instance B's own, independently-assigned local ids (verified column-by-column), never
    the raw ids that traveled on the wire.
- **Device Management UI (Phase 6 follow-up, complete)**: one of the four slices Phase 6 originally
  bundled (table expansion, Device Management UI, file/document sync, LAN deployment docs) — the User
  explicitly chose this slice next; file/document sync and deployment docs remain undesigned.
  - New `DeviceController` (`/sync/devices`, `permission:sync.manage`, no dedicated Policy — same
    gating as the rest of Sync Center): register a device from the web UI instead of only via
    `sync:register-device`, edit a device's name/role hint, revoke (deletes its Sanctum token, marks
    `status: revoked`, clears `is_local` — the `Device` row itself is kept, since `sync_changes`/
    `sync_checkpoints`/`sync_runs` all reference it and revoking shouldn't orphan or cascade-delete
    that history), and reissue a token (for a lost/compromised token, or to reactivate a revoked
    device — deletes the old token first so a device never ends up with two live ones). `setLocal()`
    moved here from `SyncCenterController` (the route path is unchanged).
  - The "Authenticates As" field is a dropdown of only the users who actually have `sync.manage`
    (`User::permission('sync.manage')`, from Spatie's `HasPermissions` trait), not free-text — avoids
    the register-then-discover-they're-ineligible round trip the console command's `$user->can(...)`
    check produces. `DeviceRequest`'s `withValidator()` still re-checks server-side (mirrors the
    console command's check) in case of a stale dropdown or direct API misuse.
  - **One-time token reveal**: a newly issued token is session-flashed (`new_device_token`), not a
    shared Inertia prop — deliberately page-specific (a raw bearer token is sensitive) and naturally
    "shown once," the same mechanism `flash.success`/`flash.error` already use, just read directly in
    `DeviceController::index()` instead of through the global `HandleInertiaRequests` share. A
    `TokenRevealModal` opens automatically when the prop is present.
  - Verified: 9 new tests (`DeviceManagementTest` — permission gating, register/list with the
    eligible-users dropdown, registering for an ineligible user is rejected, edit leaves owner/token
    untouched, revoke deletes the token but keeps the record, reissue invalidates the old token and
    issues exactly one new one, reissuing for an ownerless device fails gracefully, and `set-local`
    still works after the move), full regression green (457/457), Pint/`tsc --noEmit` clean, and a
    full manual browser walkthrough against the real dev server: registered a device through the
    actual form, confirmed the token reveal modal showed the real plaintext token and that both
    modals were genuinely closed afterward (not just visually stacked — checked via computed
    `offsetParent`), confirmed the device appeared correctly in the table with all four row actions,
    and confirmed the sidebar's Sync Center group now lists Devices alongside Overview/History/
    Conflicts.
- **File/document sync (Phase 6 follow-up, complete)**: the third of the four slices Phase 6
  originally bundled — LAN deployment docs remain undesigned. Extends row-level sync to the three
  tables that actually carry an uploaded file: `colleges` (logo), `documents`/`document_versions`,
  `student_documents`. `faculty_documents` is deliberately, permanently out of scope: its owning
  relationship is `user_id`, and `users` was never a synced table (out of scope since Phase 1), so
  there's no uuid to translate that FK through even if the row itself joined `SYNCED_MODELS`.
  - **Hash-based, on-demand change detection — not a persisted column.** An early design synced a
    `file_hash` column, computed and stored by every upload controller; scrapped before
    implementation because it required touching three unrelated controllers (`BrandingController`,
    `DocumentVersionController`, `StudentDocumentController`) and risked staleness the moment any of
    them forgot to update it. Instead `SyncService::FILE_COLUMNS` declares which synced tables carry
    a file and on which disk, and `snapshotFor()` computes `hash('sha256', ...)` fresh from the
    actual file bytes only when building a sync payload, carried as an ephemeral `_file_hash` key
    inside the snapshot — same convention as Phase 6's `_fk_uuids`.
  - **Uuid-keyed synced-file paths, not the sender's native path.** A row's own native upload path
    (e.g. `documents/{document_id}/xyz.pdf`) embeds the *sender's* own non-portable auto-increment
    id — copying that string blindly during apply would be wrong the moment two instances have
    assigned the same conceptual row different local ids. `resolveFilePath()` instead returns a
    deterministic `synced/{table}/{uuid}` path; `resolveFileAttributes()` rewrites the file column to
    that path, wired into all three apply sites (create/fast-forward, field-level merge, and
    `applyResolution()`'s `take_remote` branch — the same three-call-site pattern `resolveForeignKeys()`
    established in Phase 6). Only rows *received* via sync get this rewritten path; a row's own
    local/native origin keeps its native path.
  - **File transfer direction always mirrors the row-sync direction that triggered it.**
    `downloadMissingFiles()` runs after a PULL and fetches FROM the same remote just pulled from;
    `uploadChangedFiles()` runs after a PUSH and uploads TO the same remote just pushed to. Neither
    assumes the other side can independently reach back — deliberately matching the LAN-hub/cloud
    topology (Admin's PC as hub, Dean/Head/Faculty over LAN or a future cloud link) this whole engine
    was built around.
  - **`needsFileTransfer()` gating**: skips re-transferring on a metadata-only update — only
    transfers when the change's operation is `created`, or the file column is specifically present in
    `changed_fields`. This matches how uploads actually work in this app: every real re-upload calls
    Laravel's `store()`, which always mints a new random filename, so a real content change always
    changes the path column too. (Confirmed against `BrandingController`, not assumed — a naive
    in-place-overwrite test that skipped the real upload flow initially made this look broken; it
    isn't, real uploads always change the path.)
  - **File-transfer failures are isolated from row-apply failures.** File transfer runs outside the
    `DB::transaction()` (Phase 5) wrapping row-level `applyIncoming()` — a file failure can't roll
    back an already-successful row apply. Failures are counted and surfaced via `SyncRun.error_message`
    (the run's `status` still reads `success`), and naturally retried next sync since the local file
    still won't match the hash.
  - New `Api\SyncFileController` (`GET`/`POST /api/sync/files/{table}/{uuid}`, same
    `auth:sanctum` + `permission:sync.manage` group as the row endpoints, `where()`-constrained to
    `[a-z_]+`/`[0-9a-f-]{36}`) resolves the file path strictly via the DB row looked up by uuid —
    never trusts a client-supplied path.
  - **Found and fixed a real, pre-existing outbox gap while proving this live** (not part of the
    file-sync design itself, but what made the live proof initially fail): the "known, deliberate
    limitation" Phase 6 already documented above — a pre-existing row that predates its table joining
    the synced set has a backfilled uuid but no outbox history — turned out to affect far more rows
    than the single `College` example Phase 6 called out. A first-ever cold sync to a fresh instance
    pulled in a `Curriculum` row whose `effective_academic_year_id` pointed at an `AcademicYear` that
    had *never* been touched since Phase 6 added it to the synced set — only 1 of this dev database's
    `academic_years` rows had an outbox entry at all. The same gap existed across `colleges`,
    `year_levels`, `departments`, `programs`, `courses`, `curricula`, `semesters`, `class_sections`,
    `document_categories` — 168 rows total lacked outbox history. `resolveForeignKeys()` correctly
    threw rather than
    silently skipping (Phase 6's designed behavior), which is what surfaced this. Formalized the fix
    as a new `sync:backfill-outbox` command (`app/Console/Commands/BackfillSyncOutbox.php`) instead of
    leaving it as an ad hoc script: iterates every table in `SyncService::syncedTables()` (a new
    public accessor added alongside the existing `modelFor()`), writes one synthetic `created` outbox
    entry per row still missing one, safe to run repeatedly. A real deployment bootstrapping a new
    instance via backup/restore should run this once afterward so the restored instance's pre-existing
    reference data is immediately eligible for FK translation, not just newly-created rows going
    forward.
  - Verified: 17 new tests (`SyncFileTest` — FK translation for `Document`/`DocumentVersion`/
    `StudentDocument`, `_file_hash` computation, `downloadMissingFiles()`/`uploadChangedFiles()`
    fetch-on-mismatch/skip-on-match/fail-without-throw/skip-non-file-changes, and
    `SyncFileController` endpoint auth/404s/exact-byte-streaming/upload-requires-existing-row) plus 4
    new tests (`SyncBackfillOutboxTest` — writes a missing entry, doesn't duplicate an existing one,
    idempotent across repeated runs, covers every synced table), full regression green (478/478),
    Pint/`tsc --noEmit` clean, and a live two-instance proof against a genuinely fresh Instance B
    (migrated, seeded with only roles/permissions/users — no reference data of its own): after running
    `sync:backfill-outbox` on Instance A to close the gap above, pushed a College logo change, a new
    Document+DocumentVersion, and a new Student+StudentDocument — all three files landed on Instance B
    byte-identical to the source (verified by sha256, not just "file exists"); a second push with no
    changes was a clean no-op (0 uploaded); replacing the College logo through a real re-upload
    (new path, new bytes) and pushing again correctly re-transferred only that one file and Instance
    B's copy updated to the new hash.

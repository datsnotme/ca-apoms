# ROLE_PERMISSIONS.md

## Roles

Implemented via `spatie/laravel-permission`, guard `web`:

| Role slug | Display name |
|---|---|
| `college-administrator` | College Administrator |
| `college-dean` | College Dean |
| `department-head` | Department Head |
| `faculty-member` | Faculty Member |

A user has exactly one role in v1 (the spec's role list is mutually exclusive by job
function). `assignRole()` is still used (not a single `role` column) so multi-role support is
available later without a schema change — see `ASSUMPTIONS.md`.

## Enforcement Layers

Every permission below is enforced in **all** of the following layers, not just hidden
navigation:

1. **Route middleware** — `role:` / `permission:` middleware (Spatie) on route groups.
2. **Policies** — one Policy per model, checked via `$this->authorize()` in controllers and
   `Gate::allows()` in Inertia page prop computation.
3. **Query scopes** — `DepartmentScope` (or explicit `where('department_id', ...)`) applied
   automatically for `department-head` and `faculty-member` so a scoped user's queries
   physically cannot return another department's rows, independent of policy checks.
4. **Frontend** — sidebar/menu items and action buttons hidden per role, purely as UX
   (never the actual security boundary).

## Permission Matrix

Legend: ✅ full access · 🟡 department/own-record scoped · 👁 view-only · ⛔ no access ·
🅿 Phase 1 · 🅿2–🅿8 later phase.

| Capability | Admin | Dean | Dept. Head | Faculty | Phase |
|---|---|---|---|---|---|
| Manage user accounts (create/edit/deactivate, assign role+department) | ✅ | ⛔ | ⛔ | ⛔ | 🅿1 |
| Manage departments | ✅ | 👁 | 👁 (own) | ⛔ | 🅿1 |
| Manage programs | ✅ | 👁 | 👁 (own dept.) | ⛔ | 🅿1 |
| Manage academic years / semesters | ✅ | 👁 | 👁 | 👁 | 🅿1 |
| View audit logs | ✅ | 👁 (summary) | ⛔ | ⛔ | 🅿1 |
| Manage curricula and courses | ✅ | 👁 | 👁 (own dept.) | ⛔ | 🅿2 |
| Register/update students | ✅ | ⛔ | 🟡 (own dept., view+limited edit) | ⛔ | 🅿2 |
| Import student data from Excel | ✅ | ⛔ | ⛔ | ⛔ | 🅿2 |
| Manage enrollment records | ✅ | ⛔ | 🟡 | ⛔ | 🅿2 |
| Encode/import grades (when authorized) | ✅ | ⛔ | 🟡 (review only, not encode) | 🟡 (own classes) | 🅿2 |
| Manage student documents | ✅ | ⛔ | 👁 (own dept.) | ⛔ | 🅿2 |
| View student progress and deficiencies | ✅ | ✅ (college-wide) | 🟡 (own dept.) | 🟡 (own advisees) | 🅿3 |
| Record advising/intervention | ✅ | ⛔ | 🟡 | 🟡 (own advisees) | 🅿3 |
| Prepare graduation evaluation records | ✅ | ⛔ | 🟡 (recommend) | 🟡 (competency eval. only) | 🅿4 |
| Approve/reject graduating recommendations | ⛔ | ✅ | ⛔ | ⛔ | 🅿4 |
| Manage faculty profiles | ✅ | 👁 | 👁 (own dept.) | 🟡 (own profile) | 🅿5 |
| Assign faculty to courses/sections | ✅ | ⛔ | 🟡 | ⛔ | 🅿5 |
| Review faculty workloads | ✅ | ✅ (college-wide) | 🟡 (own dept.) | 👁 (own) | 🅿5 |
| Manage announcements/calendar/documents/settings | ✅ | 👁 | 🟡 (own dept. activities) | 👁 | 🅿6 |
| Submit research/extension records | ✅ | 👁 | 👁 (own dept.) | 🟡 (own) | 🅿7 |
| Generate authorized college reports | ✅ | ✅ (college-level) | 🟡 (dept.-level) | ⛔ | 🅿8 |
| Perform backup and restore | ✅ | ⛔ | ⛔ | ⛔ | 🅿8 |
| Archive and restore records | ✅ | ⛔ | ⛔ | ⛔ | varies |

Notes:
- The **Dean** never receives unrestricted technical system settings (users, backups, raw
  audit log internals) unless explicitly granted — per the spec's own constraint. The Dean's
  "view audit information" is a curated summary (approvals, grade finalizations,
  graduation decisions), not the raw `activity_logs` table the Admin sees.
- **Department Head** queries are automatically scoped server-side to
  `department_id = auth()->user()->department_id` — a Department Head literally cannot query
  another department's rows, not merely denied by a UI check.
- **Faculty Member** class/grade access is scoped to `faculty_assignments` rows where
  `faculty_id = auth()->id()` for the active academic year/semester.

## Phase 1 Permission List (concrete `spatie/laravel-permission` permission strings)

| Permission | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| `users.manage` | ✅ | | | |
| `departments.manage` | ✅ | | | |
| `departments.view` | ✅ | ✅ | ✅ | ✅ |
| `programs.manage` | ✅ | | | |
| `programs.view` | ✅ | ✅ | ✅ | ✅ |
| `academic-terms.manage` | ✅ | | | |
| `academic-terms.view` | ✅ | ✅ | ✅ | ✅ |
| `audit-logs.view` | ✅ | ✅ (limited) | | |

## Phase 2 Permission List (built so far: Courses, Curricula, Students, Student Documents, Enrollment, Grades, Excel Imports)

| Permission | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| `courses.manage` | ✅ | | | |
| `courses.view` | ✅ | ✅ | ✅ | ✅ |
| `curricula.manage` | ✅ | | | |
| `curricula.view` | ✅ | ✅ | ✅ | ✅ |
| `students.manage` | ✅ | | ✅ (own dept.) | |
| `students.view` | ✅ | ✅ | ✅ | ✅ |
| `students.import` | ✅ | | | |
| `student-documents.manage` | ✅ | | | |
| `student-documents.view` | ✅ | | ✅ (own dept.) | |
| `enrollment.manage` | ✅ | | ✅ (own dept.) | |
| `enrollment.view` | ✅ | ✅ | ✅ | ✅ |
| `grades.encode` | ✅ | | | ✅ (own assigned sections) |
| `grades.review` | ✅ | | ✅ (own dept.) | |
| `grades.view` | ✅ | ✅ | ✅ | ✅ |
| `grades.import` | ✅ | | | ✅ |

`enrollment.manage` covers class sections (including their schedule and primary faculty
assignment) and student enrollments/enrolled-course records — the same permission for both,
since in this phase both are the same registrar-style function. Faculty currently only view
their own department's sections/enrollments; a "my classes" faculty view is deferred to
Phase 5 (Faculty Workload) which reads the same `faculty_assignments` table.

For grades, `grades.encode` is further scoped by `ClassSectionPolicy::encodeGrades()` to the
faculty member actually assigned (`faculty_assignments`) to that specific class section — the
permission alone does not let a Faculty user grade a class they don't teach. `grades.review`
covers approve, return, finalize, **and** post-finalization single-row correction: a Department
Head performs the entire review→finalize pipeline for their own department, matching
`ASSUMPTIONS.md`'s "Department Head reviews/returns/finalizes that same batch."

Department Head `students.manage` is further scoped in `StudentPolicy` — a Department Head can
only update/delete students whose `department_id` matches their own; `students.manage` alone
does not grant cross-department access.

Student documents are the one Phase 2 capability the Dean cannot even view — only Admin can
upload/verify/delete, and only Admin plus the owning Department Head can view/download, per
`StudentDocumentPolicy`.

**Excel imports reuse the domain permission of the data being imported** rather than a
dedicated `*.import` permission per type — only `students.import` and `grades.import` were
ever seeded as distinct permissions (matching the spec's own list), so Courses, Curriculum
Courses, and Enrollment imports are gated behind `courses.manage`, `curricula.manage`, and
`enrollment.manage` respectively. The Import Data page (`/imports`) itself has no `viewAny`
gate — it is visible to every authenticated staff member (a benign read of past-import
metadata), and each upload card independently checks the uploader's permission for that
specific type before showing its form.

## Phase 3 Permission List (built so far: Progress Computation, Advising, At-Risk Monitoring, Intervention Follow-ups)

| Permission | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| `progress.view` | ✅ | ✅ (college-wide) | ✅ (own dept.) | ✅ (own advisees only) |
| `advising.manage` | ✅ | | ✅ (own dept.) | ✅ (own advisees only) |
| `advising.view` | ✅ | ✅ (college-wide) | ✅ (own dept.) | ✅ (own advisees only) |

`progress.view` is intentionally separate from `students.view` — the spec scopes Faculty to
*their own advisees* for progress/deficiency visibility, narrower than the department-wide
`students.view` access Faculty already has from Phase 2. `StudentPolicy::viewProgress()`
enforces the narrower scope: Faculty must be the `adviser_id` on the specific student record,
not merely in the same department.

`advising.manage` deliberately excludes the Dean — the spec scopes "record advising" to
Admin/Department Head/Faculty only. The Dean still gets `advising.view` for college-wide
read-only oversight, matching the same pattern as `progress.view`.
`StudentAdvisingRecordPolicy` enforces: Admin unrestricted; Department Head scoped to their
department; Faculty scoped to students where they are the *current* `adviser_id` (not a
historical one — see `ASSUMPTIONS.md`).

At-risk monitoring (`/academic-progress`, alert acknowledgment) introduces no new permission —
it reuses `progress.view` with the exact same scoping as the per-student Progress page (own
advisees for Faculty, own department for Department Head, college-wide for Admin/Dean), since
"who can see a student is at risk" is the same population as "who can see that student's
progress" in the first place.

Intervention follow-ups also introduce no new permission — they reuse `advising.manage`/
`advising.view`, since the spec's own matrix groups "record advising/intervention" as a single
capability line rather than two. `StudentInterventionFollowupPolicy` mirrors
`StudentAdvisingRecordPolicy` exactly, plus lets whoever a follow-up is assigned to update it
even if they aren't the student's adviser.

## Phase 4A Permission List (built so far: Graduation Candidate Identification, Requirement Templates)

| Permission | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| `graduation.manage` | ✅ | | | |
| `graduation.view` | ✅ | ✅ (college-wide) | ✅ (own dept.) | ✅ (own evaluation assignments only — see Phase 4B) |

`graduation.manage`/`graduation.view` cover both graduation requirement templates and
graduation candidates — the spec groups "define requirements" and "identify/nominate
candidates" as one capability area, so no second permission pair was introduced. As of Phase 4B
`graduation.view`/`graduation.manage` were further extended to also cover the competency
framework (categories/indicators) for the same reason.

`graduation.manage` is Admin-only in Phase 4A, stricter than the permission grant alone would
suggest: `GraduationCandidatePolicy` and `GraduationRequirementTemplatePolicy` additionally
hard-require the Administrator role on every create/update/delete action. Dean and Department
Head get `graduation.view` (department-scoped for Department Head, college-wide for Dean and
Admin, via `GraduationCandidate::scopeVisibleTo()`) but cannot nominate a candidate or edit a
requirement checklist — the spec's Phase 4 matrix reserves "prepare/nominate" for Admin,
distinct from "recommend" (Department Head) and "approve" (Dean), which arrive in Phase 4C.
Faculty's `graduation.view` grant was added in Phase 4B, scoped narrowly to candidates they are
assigned to evaluate — see below.

## Phase 4B Permission List (built so far: Competency Framework, Evaluator Assignment, Rating Submission)

| Permission | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| `graduation.evaluate` | ✅ (grant only; policy still Admin-gated for assignment) | | | ✅ |

`graduation.evaluate` gates competency-rating *submission* specifically — distinct from
`graduation.view` (seeing a candidate) and `graduation.manage` (nominating a candidate,
assigning evaluators, editing checklists/framework). It is granted to Faculty only.
`CompetencyRatingRequest` additionally requires the authenticated user to hold the specific
`competency_evaluators` row for that candidate — the permission alone does not let a Faculty
member rate a candidate they were never assigned to.

Faculty's `graduation.view` grant (added this phase) is scoped by
`GraduationCandidate::scopeVisibleTo()` and `GraduationCandidatePolicy::view()` to only
candidates where a `competency_evaluators` row names them as `evaluator_id` — not their whole
department. This reuses the existing `/graduation-candidates` index and show page rather than
building a separate "My Evaluations" page; a side effect is that Faculty can also view (but not
edit) the Graduation Requirements and Competency Framework settings pages, since both are gated
by the same `graduation.view` permission — considered acceptable since neither exposes
sensitive data.

Assigning/removing a competency evaluator on a candidate reuses the existing `update`
gate on `GraduationCandidatePolicy` (Admin-only via `graduation.manage`), rather than a
dedicated policy — assigning an evaluator is treated as editing the candidate record, the same
way editing the requirement checklist already was in Phase 4A.

## Phase 4C: Department Recommendation and Dean Approval

No new permissions. Two new Policy ability methods on `GraduationCandidatePolicy` —
`recommend` and `decide` — reuse the existing `graduation.view` grant instead:

| Ability | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| Recommend a candidate (`recommend`) | ⛔ | ⛔ | ✅ (own dept. only) | ⛔ |
| Approve/reject a recommendation (`decide`) | ⛔ | ✅ (college-wide) | ⛔ | ⛔ |

Both abilities check `graduation.view` (already held) plus a role check — `recommend` also
requires `$candidate->student->department_id === $user->department_id`. Neither checks
`graduation.manage`, so Admin cannot recommend or approve/reject despite holding every
permission — recommending and deciding are treated as the Department Head's and Dean's own
judgment calls, not an administrative "manage the record" action, mirroring how Phase 4A
excluded Dept. Head/Dean from nomination (each role owns exactly one step of the pipeline).

`GraduationRecommendationService::recommend()` additionally requires
`GraduationCandidate::readyForRecommendation()` (both the requirement checklist and the
competency evaluation complete) before the status can move past `under_evaluation` — this is
enforced in the service, not only hidden in the UI.

## Phase 4D: Graduation Reports (PDF) and Phase 4 Wrap-up

No new permissions. Three actions, all reusing existing gates:

| Action | Gate reused | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|---|
| Mark a candidate graduated (`confer`) | `update` (`graduation.manage` + Administrator) | ✅ | ⛔ | ⛔ | ⛔ |
| Download individual candidate report | `view` (same as the show page) | ✅ | ✅ (college-wide) | ✅ (own dept.) | ✅ (own evaluations only) |
| Download batch graduation list | `viewAny` + `scopeVisibleTo()` (same as the index page) | ✅ (college-wide) | ✅ (college-wide) | ✅ (own dept. only) | ✅ (own evaluations only, if any) |

Conferral (`confer`) is Admin-only for the same reason nomination was Admin-only in Phase 4A —
it's a registrar-style edit to the candidate record, not a Dept. Head/Dean judgment call. Both
report downloads deliberately introduce no new authorization logic: whoever can already view a
candidate can download its individual report, and the batch report is scoped by the exact same
`scopeVisibleTo()` the index page already applies, so there is only one place — not two — where
"who can see which candidates" is decided.

## Phase 5A: Faculty Profile Core

| Permission | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| `faculty-profiles.manage` | ✅ | | | |
| `faculty-profiles.view` | ✅ | ✅ (college-wide) | ✅ (own dept.) | ✅ (own profile only) |

`faculty-profiles.view` is granted to every role, but `FacultyProfile::scopeVisibleTo()` and
`FacultyProfilePolicy` narrow what each role actually sees: Admin and Dean unrestricted,
Department Head scoped to `user.department_id = auth()->department_id`, and Faculty scoped to
`profile.user_id = auth()->id()` — the same viewer-department-self three-tier shape already used
by `GraduationCandidate::scopeVisibleTo()`.

Editing is a separate check from viewing: `FacultyProfilePolicy::update()` allows it if the
actor holds `faculty-profiles.manage` (Admin — every field) **or** owns the profile (Faculty —
`specialization`/`office_location`/`bio` only, enforced field-by-field in
`FacultyProfileRequest`, not just hidden in the UI). Dean and Department Head can view but never
edit, matching the spec's "👁 (own dept.)"/"👁" entries for "Manage faculty profiles" — view-only
for them, not view+edit.

## Phase 5B: Faculty Education, Credentials, Trainings, Awards

No new permissions — all four resources (`FacultyEducation`, `FacultyCredential`,
`FacultyTraining`, `FacultyAward`) reuse `faculty-profiles.manage`/`faculty-profiles.view`
exactly as-is:

| Permission | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| Add/edit/delete education, credentials, trainings, awards | ✅ | ⛔ | ⛔ | ⛔ |
| View education, credentials, trainings, awards | ✅ | ✅ (college-wide) | ✅ (own dept.) | ✅ (own only) |

Unlike Phase 5A's core profile fields, **there is no Faculty self-edit carve-out here** — all
four resources are Admin-only to create/edit/delete. `FacultyEducationPolicy`,
`FacultyCredentialPolicy`, `FacultyTrainingPolicy`, and `FacultyAwardPolicy` each gate
`create`/`update`/`delete` on `faculty-profiles.manage` **and** the Administrator role, with no
`update()` self-ownership branch (contrast `FacultyProfilePolicy::update()`, which does have
one). Viewing is not a separate policy check at all — these records are only ever shown as part
of the faculty profile page, so `FacultyProfilePolicy::view()` (checked once, when the page
loads) is the only gate that applies.

## Phase 5C: Faculty Documents

No new permissions — `FacultyDocument` also reuses `faculty-profiles.manage`/
`faculty-profiles.view`, but the `upload` ability has a distinct shape from either Phase 5A or
5B:

| Ability | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| Upload a document | ✅ (any faculty) | ⛔ | ⛔ | ✅ (own only) |
| Verify / reject a document | ✅ | ⛔ | ⛔ | ⛔ |
| Delete a document | ✅ | ⛔ | ⛔ | ⛔ |
| View / download a document | ✅ | ✅ (college-wide) | ✅ (own dept.) | ✅ (own only) |

`FacultyDocumentPolicy::upload()` is the self-ownership-OR-permission shape (`$user->id ===
$targetFaculty->id || $user->can('faculty-profiles.manage')`) — the same pattern as
`FacultyProfilePolicy::update()` in Phase 5A. This puts document upload in a different spot from
Phase 5B's Education/Credential/Training/Award records, which have **no** self-edit branch at
all: a faculty member can upload their own supporting documents, but the document is only
`verified` once an Admin reviews it via `verify()`, which — like `delete()` — remains
strictly Admin-only, including for the uploader themselves. A Department Head can `view`/
`download` documents for faculty in their own department (read-only, same as the rest of Faculty
Management) but cannot `upload`, `verify`, or `delete`.

## Phase 5D: Faculty Workload

No new permissions — reuses `faculty-profiles.view`, matching the spec's own permission matrix
(§ "Review faculty workloads" is scoped identically to "Manage faculty profiles" — see the
Permission Matrix above):

| Ability | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| View "My Classes" (own assigned sections) | — | — | — | ✅ |
| View the workload dashboard (all faculty) | ✅ (college-wide) | ✅ (college-wide) | ✅ (own dept.) | ⛔ |
| Drill into a specific faculty member's classes | ✅ (any) | ✅ (any) | ✅ (own dept. only) | ✅ (self only) |

`FacultyWorkloadController::index()` renders one of two views from the same route, chosen by the
viewer's role: a Faculty member always sees only their own assigned sections ("My Classes"); every
other role that holds `faculty-profiles.view` sees the aggregated dashboard, scoped exactly like
`FacultyProfile::scopeVisibleTo()` (Dean/Admin unrestricted, Department Head their own department
via `user.department_id`). The drill-down route (`FacultyWorkloadController::show()`) applies the
same scoping manually: a Faculty member is rejected (403) for any id but their own, and a
Department Head is rejected for any faculty member outside `user.department_id`. There is no
`FacultyWorkloadPolicy` — see `ASSUMPTIONS.md` for why.

## Phase 6A: Announcements and Events

New permissions: `operations.manage`, `operations.view`, reused across both `Announcement` and
`Event` — matches the spec's own row exactly:

| Ability | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| Post/edit/delete a college-wide item | ✅ | ⛔ | ⛔ | ⛔ |
| Post/edit/delete a department-scoped item | ✅ (any dept.) | ⛔ | ✅ (own dept. only) | ⛔ |
| View announcements/events | ✅ (all) | ✅ (all) | ✅ (college-wide + own dept.) | ✅ (college-wide + own dept.) |

`AnnouncementPolicy`/`EventPolicy::update()` (and `delete()`, which just calls `update()`) use
the same shape as `ClassSectionPolicy::update()` (Phase 2E): `operations.manage` **and**
(Administrator **or** `department_id === user.department_id`). A college-wide item
(`department_id === null`) never satisfies the Department Head branch, so only an Admin can
touch one. `create()` only checks `operations.manage` — the department restriction is enforced
one level down, in `AnnouncementRequest`/`EventRequest::prepareForValidation()`, which silently
overwrites `department_id` to the actor's own department for anyone who isn't an Administrator,
regardless of what was submitted.

## Phase 6B: Meetings

No new permissions — `Meeting` reuses `operations.manage`/`operations.view` exactly like
`Announcement`/`Event` (Phase 6A):

| Ability | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| Schedule/edit/delete a college-wide meeting | ✅ | ⛔ | ⛔ | ⛔ |
| Schedule/edit/delete a department-scoped meeting | ✅ (any dept.) | ⛔ | ✅ (own dept. only) | ⛔ |
| View a meeting | ✅ (all) | ✅ (all) | ✅ (college-wide + own dept.) | ✅ (college-wide + own dept.) |
| Invite/remove attendees, mark attendance | same as edit/delete above | | | |
| Create an action item | same as edit/delete above | | | |
| Update/delete an action item's own status | ✅ | ⛔ | ✅ (own dept. meetings) | ✅ (only if assigned to them) |

`MeetingPolicy` is structurally identical to `AnnouncementPolicy`/`EventPolicy`. Attendee actions
(`MeetingAttendeeController`) are authorized directly against `MeetingPolicy::update()` — there is
no separate `MeetingAttendeePolicy`. `MeetingActionItemPolicy::create()` is likewise gated on
`MeetingPolicy::update()`, but `update()`/`delete()` add one more path: `assigned_to ===
user.id` also passes, so a Faculty member who cannot manage the meeting at all can still update
(including complete) or remove the specific action item assigned to them — mirroring
`StudentInterventionFollowupPolicy` (Phase 3D) exactly.

## Phase 6C: Tasks

**No new permission — and no permission check at all.** `Task` sits outside the spatie
permission system entirely; it's a personal delegation utility, not a spec-defined operations
capability. `TaskPolicy::viewAny()`/`create()` return `true` unconditionally.

| Ability | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| Create a task (for self or anyone) | ✅ | ✅ | ✅ | ✅ |
| View a task | ✅ (any) | only if creator/assignee | only if creator/assignee | only if creator/assignee |
| Fully edit a task (title/assignee/due date) | ✅ (any) | only if creator | only if creator | only if creator |
| Update a task's own status | ✅ (any) | only if creator/assignee | only if creator/assignee | only if creator/assignee |
| Delete a task | ✅ (any) | only if creator | only if creator | only if creator |

Every role has identical rules here — the distinction that matters isn't role, it's
**relationship to the task** (creator, assignee, or neither). `TaskPolicy::update()` allows the
creator, the assignee, or an Admin; `TaskRequest::rules()` then narrows what a plain assignee
(not creator, not Admin) may actually change to `status`/`notes`, mirroring the field-level split
`FacultyProfileRequest` uses (Phase 5A). `delete()` is narrower still — creator or Admin only, not
the assignee — see `ASSUMPTIONS.md` for why this diverges from `MeetingActionItemPolicy`'s
symmetric update/delete (Phase 6B).

## Phase 6D: Internal Requests

No new permissions — `InternalRequest` reuses `operations.manage`/`operations.view` a third time
(after Announcements/Events and Meetings), but with a visibility split none of the earlier Phase 6
resources have:

| Ability | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| Submit a request | ✅ | ✅ | ✅ | ✅ |
| View a request | ✅ (all) | ✅ (all) | ✅ (own dept., all requesters) | ✅ (own requests only) |
| Approve/reject a request | ✅ (any dept., except their own request) | ⛔ | ✅ (own dept., except their own request) | ⛔ |
| Cancel a request | requester only, while pending | requester only, while pending | requester only, while pending | requester only, while pending |

The key difference from `Announcement`/`Event`/`Meeting`: Faculty do **not** see their
department's requests broadly, only their own — requests aren't broadcast content, and a
colleague's leave request isn't something every Faculty member in the department should see.
`InternalRequestPolicy::review()` also blocks self-review outright: `operations.manage` alone is
not sufficient if the reviewer and the requester are the same person, regardless of role — an
Admin cannot approve their own request, and neither can a Department Head. See `ASSUMPTIONS.md`
for the full reasoning.

## Phase 6E: Document Repository

No new permission for documents themselves — `Document`/`DocumentVersion` reuse
`operations.manage`/`operations.view` a fourth time, in the same shape as Announcements/Events/
Meetings:

| Ability | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| Manage document categories (add/remove) | ✅ | ⛔ | ⛔ | ⛔ |
| Upload/edit/delete a college-wide document | ✅ | ⛔ | ⛔ | ⛔ |
| Upload/edit/delete a department-scoped document | ✅ (any dept.) | ⛔ | ✅ (own dept. only) | ⛔ |
| Upload a new version of a document they can manage | ✅ | ⛔ | ✅ (own dept.) | ⛔ |
| View/download a document | ✅ (all) | ✅ (all) | ✅ (college-wide + own dept.) | ✅ (college-wide + own dept.) |

Document category management is narrower than everything else here: it checks
`hasRole(Administrator)` directly, not just `operations.manage`, so a Department Head — who does
hold `operations.manage` and can freely manage documents in their own department — still cannot
add or remove entries from the shared category list. This mirrors
`GraduationRequirementTemplatePolicy` (Phase 4A), the only other Admin-narrowed-from-`.manage`
resource in the app.

## Phase 6F: Notifications

No new permission. Notifications have no independent authorization model — a user only ever
reads their own notification inbox, checked by direct ownership comparison
(`notifiable_id === auth()->id()`) in `NotificationController`, not by role or department. Every
role receives whichever of the four notification types apply to them, purely as a byproduct of
already-established Phase 6A–6D authorization (e.g. only someone who could already see a
department's announcements is in the audience notified about a new one).

| Ability | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| Receive a notification (any of the 4 triggers, per existing scoping) | ✅ | ✅ | ✅ | ✅ |
| View/mark read their own notifications | ✅ | ✅ | ✅ | ✅ |
| View/mark read someone else's notifications | ⛔ | ⛔ | ⛔ | ⛔ |

**Phase 6 (College Operations) is now fully complete.** This table has grown across all six
sub-phases (6A–6F) without a single Phase-6-specific permission beyond `operations.manage`/
`operations.view`, reused five times over (Announcements/Events, Meetings, Internal Requests,
Documents) — plus Tasks and Notifications, which deliberately sit outside the permission system
entirely (ownership-based, not role-based). This table continues to grow every phase; each
phase's implementation report appends its new permissions here.

## Phase 7A: Research

New permission pair, `research-extension.manage`/`research-extension.view` — shared with
Extension (Phase 7B), since the spec gives both an identical row:

| Ability | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| Create a research project | ✅ (any dept.) | ⛔ | ⛔ | ✅ (own dept. only) |
| View a research project | ✅ (all) | ✅ (all) | ✅ (own dept. only) | ✅ (own dept. only) |
| Edit/delete a research project | ✅ (any) | ⛔ | ⛔ | ✅ (only if project lead) |
| Add/remove a member | same as edit/delete above | | | |
| Add/remove an output | same as edit/delete above | | | |

This is a **deliberate departure from Phase 6's pattern**: Phase 6 granted `operations.manage` to
Department Head fairly liberally (a free design choice under ambiguous spec guidance). Here the
spec row is explicit — `✅ | 👁 | 👁 (own dept.) | 🟡 (own) | 🅿7` — so `research-extension.manage`
is seeded to **Administrator only**, and Department Head receives `research-extension.view` and
nothing more, even for their own department's projects. Faculty's manage-own (🟡) ability comes
entirely from `ResearchProjectPolicy`, not a role-level `.manage` grant: `create()` passes for any
Faculty (`hasRole()` check, no permission needed), and `update()`/`delete()` pass only for the
project's lead member (`research_members.is_lead = true`) or an Administrator. A project's
creator is auto-added as its lead member in the same transaction as creation, so a new project is
always immediately editable by whoever made it.

`ResearchMemberPolicy`/`ResearchOutputPolicy` have no rules of their own — `create()`/`delete()`
both delegate to `ResearchProjectPolicy::update()` on the parent project, the same pattern already
used for `CompetencyEvaluator` (Phase 4B) and `MeetingAttendee`/`MeetingActionItem` (Phase 6B).

## Phase 7B: Extension

**No new permission** — reuses `research-extension.manage`/`research-extension.view` exactly as
Phase 7A anticipated, since the spec gives Research and Extension an identical permission row:

| Ability | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| Create an extension project | ✅ (any dept.) | ⛔ | ⛔ | ✅ (own dept. only) |
| View an extension project | ✅ (all) | ✅ (all) | ✅ (own dept. only) | ✅ (own dept. only) |
| Edit/delete an extension project | ✅ (any) | ⛔ | ⛔ | ✅ (only if project lead) |
| Add/remove a member | same as edit/delete above | | | |
| Add/remove an activity | same as edit/delete above | | | |
| Add/remove a beneficiary | same as edit/delete above | | | |

`ExtensionProjectPolicy` is structurally identical to `ResearchProjectPolicy` (Phase 7A) — same
`.view`/`.manage` gates, same Faculty-any-role `create()`, same lead-membership-or-`.manage`
`update()`/`delete()`. `ExtensionMemberPolicy`, `ExtensionActivityPolicy`, and
`ExtensionBeneficiaryPolicy` all delegate `create()`/`delete()` to
`ExtensionProjectPolicy::update()` on the parent project — no rules of their own, the same
child-authorized-via-parent pattern used throughout this app.

## Phase 7C: Facilities

**No new permission** — `Facility` reuses `operations.manage`/`operations.view`. The spec has no
facilities-specific permission row (unlike Research/Extension's explicit 🅿7 row), so this is a
design choice following Phase 6's precedent, not a literal spec requirement:

| Ability | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| Register/edit/delete a shared, college-wide facility | ✅ | ⛔ | ⛔ | ⛔ |
| Register/edit/delete a department-scoped facility | ✅ (any dept.) | ⛔ | ✅ (own dept. only) | ⛔ |
| View a facility | ✅ (all) | ✅ (all) | ✅ (shared + own dept.) | ✅ (shared + own dept.) |
| Assign a facility to a class schedule | Anyone who can already manage the class section (`enrollment.manage`, Phase 2E) — not gated by `operations.*` | | | |

`FacilityPolicy` is structurally identical to `AnnouncementPolicy`/`EventPolicy`/`MeetingPolicy`
(Phase 6): nullable-department visibility, `.manage`-gated create, Admin-or-own-department
update/delete. Assigning a facility to a `class_schedules` row is authorized entirely through the
existing `ClassSectionPolicy::update()` (whoever can edit the class section can pick any facility
visible to them) — there is no separate "who can assign facilities" permission.

## Phase 7D: Equipment

**No new permission** — `Equipment` reuses `operations.manage`/`operations.view`, same as
`Facility` (Phase 7C). The spec has no equipment-specific permission row either:

| Ability | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| Register/edit/delete shared, college-wide equipment | ✅ | ⛔ | ⛔ | ⛔ |
| Register/edit/delete department-scoped equipment | ✅ (any dept.) | ⛔ | ✅ (own dept. only) | ⛔ |
| View equipment | ✅ (all) | ✅ (all) | ✅ (shared + own dept.) | ✅ (shared + own dept.) |
| Record a borrowing or return | same as edit/delete above | | | |
| Report or complete a maintenance record | same as edit/delete above | | | |
| View the accountability report | Same as `viewAny` — anyone with `operations.view`, scoped by the same visibility rule as the equipment index | | | |

`EquipmentPolicy` is structurally identical to `FacilityPolicy`. `EquipmentBorrowingPolicy`,
`EquipmentReturnPolicy`, and `EquipmentMaintenancePolicy` all delegate `create()` (and, for
maintenance, `update()` — used to mark a record complete) to `EquipmentPolicy::update()` on the
parent equipment item — no rules of their own, the same child-authorized-via-parent pattern used
throughout Phase 7. Business rules (equipment must be `available` to borrow or send for
maintenance; a borrowing must not already have a return) are enforced in the FormRequests and
controllers, not the Policy layer — they're workflow invariants, not authorization.

## Phase 8B: Reports

One new permission, matching the spec's explicit "Generate authorized college reports" row:

| Ability | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| Generate authorized college reports | ✅ (college-level) | ✅ (college-level) | 🟡 (department-level) | ⛔ |

`reports.view` is granted to Admin, Dean, and Department Head, and deliberately withheld from
Faculty. There is **no second permission tier** for the college-vs-department distinction — every
role that holds `reports.view` hits the same routes; `ReportService` scopes every query internally
(`departmentIdFor()` forces a Department Head to their own `department_id`, while Admin/Dean may
optionally narrow via a `department_id` filter or see everything by default).

`ReportController` has **no dedicated Policy class** — same pattern as `AuditLogController`
(Phase 1). Authorization is `Route::middleware('permission:reports.view')` at the route layer;
there is no per-report-instance ownership to check (reports aren't rows a user owns), so a Policy
would have nothing to adjudicate beyond the permission check the route middleware already performs.

## Phase 8C: Backup and Restore

One new permission, matching the spec's explicit "Perform backup and restore" row — the strictest
access tier in the whole permission matrix, with no partial/department-scoped grant for anyone:

| Ability | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| Perform backup and restore | ✅ | ⛔ | ⛔ | ⛔ |

`backups.manage` exists only in the seeder's `PERMISSIONS` list (so Administrator receives it via
`syncPermissions(self::PERMISSIONS)`) and appears in **no other role's grant array at all** — the
same `users.manage`-style pattern (Phase 1) for a capability with zero partial access for any other
role. `BackupController` has no dedicated Policy class, same reasoning as `AuditLogController` and
`ReportController`: authorization is `Route::middleware('permission:backups.manage')` at the route
layer, and there is no per-backup ownership to adjudicate (a backup file isn't "owned" by the user
who created it — any Admin can act on any backup).

## Post-Launch: System Logo

One new permission, Admin-only with no partial grant, same shape as `backups.manage`:

| Ability | Admin | Dean | Dept. Head | Faculty |
|---|---|---|---|---|
| Upload/change/remove the system logo | ✅ | ⛔ | ⛔ | ⛔ |

`branding.manage` exists only in the seeder's `PERMISSIONS` list, same `users.manage`/
`backups.manage`-style pattern. `BrandingController` has no dedicated Policy class for the same
reason as `BackupController` — there is no per-record ownership to adjudicate; it always operates
on the single seeded `College` row (see `ASSUMPTIONS.md`'s single-college assumption).
Authorization is `Route::middleware('permission:branding.manage')` at the route layer. The
rendered logo itself (`College.logo_url`, shared globally via `HandleInertiaRequests`) is visible
to everyone, including unauthenticated visitors on the login page — only *changing* it is gated.

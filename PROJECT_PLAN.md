# PROJECT_PLAN.md

## College of Agriculture Academic Progress and Operations Management System (CA-APOMS)

## 1. Purpose

CA-APOMS centralizes academic record-keeping and college operations for a College of
Agriculture: student academic records and progress monitoring, faculty profiles and
workload, graduating-student evaluation, research and extension tracking, facility/equipment
management, and general college operations (announcements, meetings, tasks, requests,
document repository).

This is a **new, standalone project**, unrelated in codebase to any prior Next.js prototype.
It is built from scratch on Laravel, and is developed **phase by phase**, with each phase
fully implemented, migrated, seeded, and tested before the next begins.

## 2. Technology Stack

| Layer | Choice | Notes |
|---|---|---|
| Backend framework | Laravel 12 (PHP 8.2+) | Matches installed PHP 8.2.12 |
| Database | MySQL-compatible (MariaDB 10.4 via XAMPP in dev) | InnoDB/utf8mb4 |
| Auth scaffolding | Laravel Breeze (React + Inertia + TypeScript stack) | Login, registration disabled for self-signup (admin-created accounts only), password reset, profile |
| Authorization | Laravel Policies + Gates + `spatie/laravel-permission` | Roles: College Administrator, College Dean, Department Head, Faculty Member |
| Audit logging | `spatie/laravel-activitylog` + a custom `login_logs` table | Central `activity_logs` table with polymorphic subject |
| Frontend | React 18 + TypeScript, Inertia.js, Tailwind CSS | Server-driven routing via Inertia, no separate REST API for page rendering |
| Charts | Chart.js (`react-chartjs-2`) | Added in Phase 8 (Analytics) |
| Excel import/export | `maatwebsite/excel` | Added when the first import module is built (Phase 2) |
| PDF generation | `barryvdh/laravel-dompdf` | Added in Phase 4D (graduation candidate/batch reports) |
| Queues | Database queue driver (dev), Redis-ready for production | Added when first queued job is needed (large imports) |
| Testing | Pest (Laravel 12 default) | Feature tests per module |

Only stable, actively maintained packages compatible with Laravel 12 / PHP 8.2 are used.
Packages are added **when the module that needs them is built**, not all up front, to keep
`composer.json` honest about what's actually in use.

## 3. Folder Structure (proposed, standard Laravel + Inertia layout)

```
CA-APOMS/
├── app/
│   ├── Console/Commands/          # scheduled commands (reminders, credential-expiry checks)
│   ├── Enums/                     # centralized status/enum classes (StudentStatus, GradeStatus, ...)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/             # college admin-only controllers
│   │   │   ├── Academic/          # departments, programs, academic years, semesters
│   │   │   ├── Student/
│   │   │   ├── Faculty/
│   │   │   ├── Grading/
│   │   │   ├── Graduation/
│   │   │   ├── Operations/
│   │   │   └── ...
│   │   ├── Middleware/            # EnsureRole, ScopeToDepartment, ForcePasswordChange, ...
│   │   ├── Requests/               # Form Request validation per action
│   │   └── Resources/              # API/Inertia prop transformers
│   ├── Models/
│   ├── Policies/
│   ├── Services/                   # business logic (ProgressComputationService, WorkloadService, ...)
│   ├── Scopes/                     # DepartmentScope and similar global/query scopes
│   └── Providers/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── resources/
│   ├── js/
│   │   ├── Components/             # shared UI components (Button, Table, Badge, Modal, ...)
│   │   ├── Layouts/                 # AppLayout (sidebar/topbar), AuthLayout
│   │   ├── Pages/                   # Inertia pages, mirrors controller structure
│   │   ├── types/                   # shared TypeScript types
│   │   └── hooks/
│   └── css/
├── routes/
│   ├── web.php
│   ├── auth.php                     # Breeze-generated
│   └── console.php                  # scheduled tasks
├── tests/
│   ├── Feature/
│   └── Unit/
├── PROJECT_PLAN.md
├── DATABASE_DESIGN.md
├── ROLE_PERMISSIONS.md
├── ASSUMPTIONS.md
├── README.md
├── INSTALLATION.md
└── .env.example
```

Deeper documentation (`DEPLOYMENT.md`, `BACKUP_RESTORE.md`, `USER_GUIDE.md`,
`API_DOCUMENTATION.md`) is written once there is real deployment/backup/API behavior to
document — see `ASSUMPTIONS.md` for the scoping decision.

## 4. Development Phases

Each phase is fully implemented, migrated, seeded, tested, and manually verified before the
next phase starts, per the project's own phase-gate requirement.

| Phase | Scope | Status |
|---|---|---|
| 1. Foundation | Project setup, auth (Breeze), roles/permissions, colleges, departments, programs, academic years/semesters, user management, base layout, audit logging | ✅ Complete |
| 2. Core Academic Management | Students, student documents, curricula, courses, enrollment, class sections, grade management, Excel imports | ✅ Complete |
| 3. Student Progress | Progress computation service, curriculum checklist, deficiency detection, at-risk monitoring, advising/intervention, progress reports | ✅ Complete |
| 4. Graduating Student Evaluation | Candidate identification, graduation requirements, competency indicators, evaluator assignment, department recommendation, dean approval, graduation reports (PDF) | ✅ Complete |
| 5. Faculty Management | Faculty profiles, education, credentials, trainings, accomplishments, documents, workloads, teaching assignments | ✅ Complete |
| 6. College Operations | Announcements, calendar, meetings, tasks, internal requests, notifications, document repository | ✅ Complete |
| 7. Agriculture Support Modules | Research, extension, facilities, laboratories, farms, equipment, borrowing/maintenance | ✅ Complete (7A, 7B, 7C, 7D) |
| 8. Analytics and Finalization | Role-based dashboards, reports, charts, PDF/Excel output, performance/security/accessibility review, full test pass, deployment docs | ✅ Complete (8A, 8B, 8C, 8D) |

## 5. Phase 1 Detailed Scope

**Goal:** a runnable, authenticated, role-aware administrative shell with the organizational
data foundation everything else depends on.

Included:
- Laravel 12 + Breeze (React/Inertia/TypeScript) + Tailwind scaffold
- `spatie/laravel-permission` roles: `college-administrator`, `college-dean`,
  `department-head`, `faculty-member`
- `spatie/laravel-activitylog` + `login_logs` table
- Tables: `colleges`, `departments`, `programs`, `academic_years`, `semesters`, plus
  Spatie's `roles`/`permissions`/pivot tables, plus `users` extended with institutional
  fields
- User management CRUD (admin only): create/edit/deactivate, role + department assignment,
  forced password change on first login
- Department/Program/Academic Year/Semester CRUD (admin only, soft-deletable)
- Base layout: collapsible sidebar (full module list from the spec, items hidden per role),
  topbar, breadcrumbs, notification/profile menu placeholders, agriculture green theme
- Department-scoping query scope, applied to any model with a `department_id`
- Policies for every Phase 1 resource
- Audit log viewer (admin only) with filters
- Dev-seeded accounts for all four roles
- Feature tests: authentication, authorization/department-scoping, user management, department/program CRUD

Explicitly **not** in Phase 1 (deferred to their named phase): students, curricula, courses,
enrollment, grades, faculty profiles/workload, graduation evaluation, research/extension,
facilities/equipment, operations submodules, dashboards/charts/reports, Excel/PDF I/O, queues,
scheduled commands. Sidebar links for these modules are present (per the UI spec) but route to
"not yet implemented" placeholders or are simply absent from routes until their phase — this
is documented per-item in `ASSUMPTIONS.md`.

## 6. Phase 2 Detailed Scope

**Goal:** the full academic records pipeline — a student can be registered, placed on a
curriculum, enrolled into class sections, and graded, with every step audited and
department-scoped, plus bulk Excel import for all of it.

Included:
- `year_levels`, `sections`, `grading_scales`/`grading_scale_values` (configurable grading
  policy, not hard-coded)
- Curricula and Courses: course catalog with prerequisites/corequisites, curriculum checklist
  builder (add/remove courses per year level and semester)
- Students: full profile CRUD (personal, academic placement, guardian/emergency contact,
  addresses), classification/status with an automatically-written status-history audit trail
- Student Documents: category-tagged uploads on a non-public disk, authenticated download,
  Admin-only verify/reject workflow with Department-Head view access
- Enrollment and Class Sections: class section CRUD (course + semester + capacity + primary
  faculty + meeting schedule), student enrollment per semester, add/drop of individual courses
  with duplicate/capacity checks
- Grade Management: draft → submit → review → finalize workflow batched at the class-section
  level (`grade_submissions`), with every individual grade *value* change logged
  (`grade_change_logs`) regardless of batch state, and a single-row post-finalization
  correction path that doesn't require reopening the whole class
- Excel Imports: template download, row-by-row validate-then-persist with per-row error
  reporting, for all five data types above, reusing the same services (`EnrollmentService`,
  `GradeService`) the manual UI uses so a bulk import can't bypass a business rule
- Feature tests for every module above (97 passing across Phase 1 + Phase 2)
- Realistic seed data spanning every module: 4 departments, 5 programs, 4 curricula, 8 courses,
  32 students, 8 class sections with faculty/schedule, 12 enrollments with enrolled courses

Explicitly **not** in Phase 2 (deferred to their named phase): progress computation/deficiency
detection (Phase 3), graduation evaluation (Phase 4), faculty profiles/workload beyond the
single primary-assignment-per-section built here (Phase 5), a true pre-commit import preview
step (not required by the DB design — see `ASSUMPTIONS.md`).

## 7. Phase 3 Detailed Scope

**Goal:** turn the academic records Phase 2 built into an actionable progress-monitoring
workflow — every student's curriculum-checklist standing, GWA, and deficiencies computed on
demand; at-risk students automatically surfaced; and advising/intervention tracked through to
completion, all department-scoped exactly like every prior phase.

Included:
- Progress Computation: `ProgressComputationService` computes checklist status
  (completed/failed/incomplete/in_progress/dropped/not_taken/pending) per curriculum course on
  every page load — never cached — using "best attempt wins" so a passed retake supersedes an
  earlier fail; GWA (Philippine 1.00–5.00 convention, lower is better) and curriculum-completion
  percentage; `academic_deficiencies` persisted (only piece that needs a resolution workflow)
  and auto-resolved once no longer applicable
- New `progress.view` permission, narrower than `students.view` — Faculty scoped to their own
  advisees specifically, not their whole department
- Advising: `students.adviser_id` (from Phase 2C) remains the single editable field; a new
  `student_advisers` history log is auto-written by a `Student` model event on every
  reassignment, mirroring `student_status_histories`. Session notes
  (`student_advising_records`) with recommendations and a follow-up flag
- At-Risk Monitoring: three rules (2+/4+ unresolved deficiencies, GWA at/above the passing
  threshold, concerning enrollment status) independently evaluated and resolved per student,
  re-synced on every visit to the scoped `/academic-progress` list; acknowledgment tracked and
  reset if an alert re-triggers after resolution
- Intervention Follow-ups: action items optionally linked to an advising record or an alert,
  assignable to any adviser/faculty, tracked through pending → in_progress →
  completed/cancelled with who/when recorded
- New `advising.manage`/`advising.view` permissions covering both advising records and
  intervention follow-ups (the spec groups them as one capability), deliberately excluding Dean
  from `.manage`
- A browser-print affordance on the consolidated Progress page in place of a generated PDF
  report — PDF export stays reserved for Phase 4 per the technology table above
- Feature tests for every module above (134 passing across Phase 1–3)

Explicitly **not** in Phase 3 (deferred to their named phase): graduation evaluation (Phase 4),
a generated/downloadable PDF progress report (Phase 4's PDF tooling), a genuine multi-semester
enrollment-gap detector beyond the simple status check built here, queued/scheduled alert
re-evaluation (currently synchronous and scoped to the viewer — see `ASSUMPTIONS.md`).

## 8. Phase 4 Detailed Scope

**Goal:** carry an eligible student from nomination through Dean approval to `graduated`, with
every step (requirements checklist, competency evaluation, department recommendation, dean
approval) tracked on a single candidate record and gated by exactly the roles the spec assigns
to each step — preparation is Admin-only, recommendation is Department Head, approval is Dean.

### Sub-phase 4A: Graduation Candidate Identification and Requirements — ✅ Complete

- `GraduationCandidateService::identifyEligibleStudents()` — active students at 100% curriculum
  completion (`ProgressComputationService`) with zero unresolved deficiencies
- Nomination (`GraduationCandidateService::nominate()`) snapshots GWA, completion %, and
  deficiency count at the moment of nomination (the one deliberate exception to this project's
  "compute on demand" rule — see `ASSUMPTIONS.md`) and auto-generates the requirement checklist
  from matching `graduation_requirement_templates` (program-specific or universal)
  - A student cannot be nominated twice while an active (non-`rejected`) candidacy exists
- `GraduationRequirementTemplateController` — Admin-only CRUD for the checklist definitions,
  scoped per-program or applying to all programs
- `StudentGraduationRequirementController` — per-requirement satisfy/waive/reset, recording
  who and when (Admin-only)
- New `graduation.manage`/`graduation.view` permissions (Admin `.manage`; Admin/Dean/Dept. Head
  `.view`, department-scoped for Dept. Head via `GraduationCandidate::scopeVisibleTo()`); no
  Faculty grant yet (Phase 4B)
- No `graduation_evaluations` table — `GraduationCandidate.status` is the umbrella status field
  carried through every remaining sub-phase
- 10 feature tests covering eligibility, nomination/snapshotting, duplicate-candidacy rejection,
  requirement satisfy/waive/reset, and role-scoped visibility (144 passing across Phase 1–4A)

### Sub-phase 4B: Competency Evaluation — ✅ Complete

- `CompetencyCategory`/`CompetencyIndicator` — Admin-managed rating framework (categories group
  indicators), reusing the `graduation.manage`/`.view` permission pair from Phase 4A
- `CompetencyEvaluator` — assigns a Faculty member (same department as the candidate's student,
  enforced server-side) as an evaluator for a candidate; assigning the first evaluator
  auto-transitions the candidate from `nominated` to `under_evaluation`
- `CompetencyRating` — one rating (1–5) + optional remarks per (evaluator, indicator) pair,
  submitted by the assigned evaluator only
- `GraduationCandidate::evaluationComplete()` — computed, true once every assigned evaluator has
  rated every currently-defined indicator, mirroring `requirementsComplete()` from Phase 4A
- New `graduation.evaluate` permission (Faculty only), gating rating submission specifically;
  `graduation.view` extended to Faculty, scoped to only the candidates they are assigned to
  evaluate — reusing the existing candidate list/show pages rather than a separate
  "My Evaluations" page
- Feature tests covering status transition on first assignment, duplicate-assignment
  rejection, role/department validation on evaluator assignment, rating authorization and range
  validation, `evaluationComplete()` transitions, Faculty-scoped visibility, and evaluator
  removal cascading ratings (155 passing across Phase 1–4B)

### Sub-phase 4C: Department Recommendation and Dean Approval — ✅ Complete

- `GraduationRecommendationService::recommend()` — Department Head recommends a candidate (own
  department only), gated on `GraduationCandidate::readyForRecommendation()`
  (`requirementsComplete() && evaluationComplete()`); moves status `under_evaluation` →
  `recommended`
- `GraduationRecommendationService::approve()`/`reject()` — Dean decides on a recommended
  candidate (college-wide); moves status `recommended` → `approved`/`rejected`
- No `graduation_recommendations`/`graduation_approvals` tables — six nullable columns
  (`recommended_by`/`recommended_at`/`recommendation_remarks`,
  `decided_by`/`decided_at`/`decision_remarks`) added directly to `graduation_candidates`
  instead, since a candidate only ever receives one recommendation and one decision
- New `recommend`/`decide` Policy ability methods on `GraduationCandidatePolicy`, reusing the
  existing `graduation.view` grant (no new permission strings) narrowed by role and department;
  `graduation.manage` deliberately excluded so Admin cannot recommend or approve
- Frontend: a "Recommendation & Approval" card on the candidate show page, appearing once a
  candidate leaves `nominated`, with role-appropriate actions and a read-only audit trail of who
  recommended/decided and when
- Feature tests covering the full lifecycle, both gating conditions (checklist/evaluation
  incomplete, wrong status), and role/department authorization for both actions (166 passing
  across Phase 1–4C)
- Live browser walkthrough of the complete pipeline: Admin nominates and assigns an evaluator →
  Faculty rates → Department Head recommends → Dean approves, verified end to end

### Sub-phase 4D: Graduation Reports (PDF) and Phase 4 Wrap-up — ✅ Complete

- `barryvdh/laravel-dompdf` installed (first PDF dependency in the project, per the technology
  table above); reports are plain Blade views (`resources/views/pdf/*.blade.php`), not Inertia
  pages
- `GraduationRecommendationService::markGraduated()` — Admin-only final confirmation that an
  approved candidate actually graduated, reusing the existing `update` gate; adds one
  `graduated_at` column to `graduation_candidates`, no new table
- `GraduationReportController@show` — individual candidate PDF (checklist, competency ratings,
  recommendation, decision, conferral), authorized via the same `view` ability as the candidate
  page
- `GraduationReportController@batch` — official graduation list PDF for a given academic
  year/semester, scoped by the existing `GraduationCandidate::scopeVisibleTo()` (no separate
  authorization logic)
- Frontend: "Download Report (PDF)" link on the candidate page, "Mark as Graduated" action for
  approved candidates, and a "Download Graduation List (PDF)" control with year/semester filters
  on the candidates index
- Feature tests covering conferral (success, wrong-status rejection, role restriction) and both
  report endpoints (authorized/unauthorized access, missing filter validation) — 173 passing
  across all of Phase 1–4
- Live browser walkthrough of the complete Phase 4 pipeline through to PDF: nominate → assign
  evaluator → rate → recommend → approve → download individual report → mark graduated →
  download the batch graduation list — verified end to end, including catching and fixing a
  frontend display bug where the "Dean's Decision" badge showed the candidate's *current* status
  instead of the decision actually made, once a candidate progressed past `approved` to
  `graduated`

**Phase 4 is now fully complete.** See `DATABASE_DESIGN.md`, `ASSUMPTIONS.md`, and
`ROLE_PERMISSIONS.md` for the full schema, design rationale, and permission matrix across all
four sub-phases.

## 9. Phase 5 Detailed Scope

**Goal:** give the college a system of record for who its faculty are (rank, employment status,
qualifications, accomplishments, documents) and how their teaching load breaks down — reusing
the `faculty_assignments` table Phase 2E already built for "who teaches which section," rather
than duplicating it.

### Sub-phase 5A: Faculty Profile Core — ✅ Complete

- `FacultyProfile` — one-to-one with `User` (rank, employment status, specialization, office
  location, date hired, bio), lazily created via `firstOrCreate` on first view/edit
- New `faculty-profiles.manage` (Admin, every field) and `faculty-profiles.view` (Admin/Dean
  college-wide, Department Head own department, Faculty own profile only) permissions
- Field-level split on edit: Admin can change every field; a Faculty member editing their own
  profile is limited to `specialization`/`office_location`/`bio` — `academic_rank`/
  `employment_status`/`date_hired` are HR-controlled and excluded server-side in
  `FacultyProfileRequest`, not just hidden in the UI
- `FacultyProfile::scopeVisibleTo()` mirrors the same Admin/Dean-unrestricted,
  Department-Head-own-department, self-only shape used by `GraduationCandidate::scopeVisibleTo()`
- Frontend: a searchable Faculty Profiles index (scoped per role) and a combined view/edit page
  that renders read-only for viewers without edit rights
- Feature tests covering lazy creation, full vs. limited field edits, and view/edit scoping
  across all four roles (182 passing across Phase 1–5A)

### Sub-phase 5B: Faculty Education, Credentials, Trainings, Awards — ✅ Complete

- Four child resources under a faculty member (keyed on `user_id`, not `faculty_profile_id`):
  `FacultyEducation` (degree/level/institution/year), `FacultyCredential`
  (license/issuing body/issued & expiry dates), `FacultyTraining`
  (title/provider/type/dates/hours), `FacultyAward` (title/awarding body/date/description)
- No new permissions — all four reuse `faculty-profiles.manage`/`.view`; unlike the Phase 5A
  core profile fields, there is no Faculty self-edit carve-out here, since these are treated as
  verifiable institutional records rather than self-reported facts
- No `view`/`viewAny` policy methods on any of the four — visibility is entirely gated by
  `FacultyProfilePolicy::view()` when the profile page loads, the same "parent authorization is
  the only gate" pattern as `StudentGraduationRequirementPolicy`/`CompetencyIndicatorPolicy`
  (Phase 4)
- Frontend: four inline add/list sections on the faculty profile page (mirroring the
  Competency Framework indicator-list pattern from Phase 4B), read-only for non-Admin viewers
- Feature tests covering full CRUD for each resource, the Admin-only restriction (Faculty and
  Department Head both rejected), date-range validation, and cross-user route-mismatch
  protection (191 passing across Phase 1–5B)
- Live browser walkthrough: added an education record and a credential as Admin, confirmed both
  render correctly on reload, then logged in as the Faculty member and confirmed they see the
  same records read-only with no add/remove controls. Caught and fixed a real display bug during
  this walkthrough — date columns (`issued_date`, `expiry_date`, `start_date`, `end_date`,
  `date_awarded`) were rendering as raw ISO timestamps instead of `YYYY-MM-DD`, since Eloquent's
  `date` cast serializes to full ISO 8601 in JSON; fixed with the same `.slice(0, 10)` truncation
  already used elsewhere in the app for cast-date display

### Sub-phase 5C: Faculty Documents — ✅ Complete

- `FacultyDocument` — keyed on `user_id` (same as the four Phase 5B resources), with
  `category` (diploma/transcript of records/professional license/appointment letter/certificate
  of employment/performance evaluation/other), stored file metadata (`file_path`,
  `original_filename`, `file_type`, `file_size`), and a full `verification_status`
  (pending/verified/rejected) review workflow with `verified_by`/`verified_at`/`remarks`
- No new permissions — reuses `faculty-profiles.manage`/`.view`, but the `upload` ability has a
  distinct self-OR-admin shape (`FacultyDocumentPolicy::upload()`): a faculty member may upload
  their own supporting documents, unlike Phase 5B's Admin-only records, because the upload only
  enters as `pending` — it is not treated as verified fact until an Admin reviews it via
  `verify()`, which (like `delete()`) remains strictly Admin-only, including for the uploader
  themselves
- `FacultyDocument::scopeVisibleTo()` uses the same Admin/Dean-unrestricted,
  Department-Head-own-department, self-only shape as `FacultyProfile`; a Department Head can
  view/download but never upload, verify, or delete
- Soft deletes on `faculty_documents` (unlike the hard-deleted Phase 5B tables), since deleting a
  document also removes its file from storage — the DB row is kept as a tombstone
- `barryvdh/laravel-dompdf`-free: documents are arbitrary uploaded files (PDF/JPG/PNG),
  downloaded via a streamed `Storage` response, not generated PDFs
- Frontend: a Documents card on the faculty profile page listing all documents with status
  badges, uploader/verifier attribution, and inline Verify/Reject controls for Admins; an upload
  form (category, title, file) shown only to Admins and to the faculty member viewing their own
  profile
- Feature tests covering admin upload, self upload, cross-faculty upload rejection, file-type
  validation, admin-only verify (Department Head rejected), self-verify rejection,
  Department-Head view/download-only access, cross-department rejection, cross-faculty view
  rejection, and delete removing both the DB row (soft) and the stored file, admin-only (201
  passing across Phase 1–5C)
- Live browser walkthrough: confirmed the Documents card renders with all 7 categories, that
  submitting without a file surfaces "The file field is required.", and — after seeding a demo
  document via `tinker` (file uploads can't be driven through the browser-automation tool) —
  that clicking Verify as Admin updates the status badge to "verified" with reviewer attribution
  and correctly hides the Verify/Reject controls afterward

### Sub-phase 5D: Faculty Workload and Phase 5 Wrap-up — ✅ Complete

- `App\Services\FacultyWorkloadService` — computes teaching load on demand from the existing
  `faculty_assignments` table (Phase 2E) joined through `class_sections` → `courses` (units) and
  `class_schedules` (meeting times); no new table
- `FacultyWorkloadController@index` — one route, two renderings by role: a Faculty member sees
  only their own assigned sections ("My Classes"); Admin/Dean/Department Head see a
  faculty-by-faculty workload dashboard (section count + total units), scoped exactly like
  `FacultyProfile::scopeVisibleTo()`
- `FacultyWorkloadController@show` — per-faculty class list drill-down from the dashboard, with
  the same Admin/Dean-unrestricted, Department-Head-own-department, self-only access rule
  enforced manually (no dedicated Policy — see `ASSUMPTIONS.md`)
- No new permissions — reuses `faculty-profiles.view`, matching the spec's own permission matrix
  ("Review faculty workloads" scoped identically to "Manage faculty profiles")
- A semester filter (dropdown, defaults to whichever `Semester.is_current = true`) applies to
  both "My Classes" and the dashboard/drill-down
- Frontend: `FacultyWorkload/Index.tsx` (role-adaptive), `FacultyWorkload/Show.tsx`
  (drill-down), sharing a `SectionsTable` component; the "Faculty Workload" nav entry (previously
  a disabled placeholder) is now live
- Feature tests covering: Faculty sees only their own current-semester classes by default;
  semester filtering; a Faculty member is forbidden from viewing another faculty member's
  workload; a Department Head's dashboard is scoped to their own department (and includes
  themselves, since a Department Head may also teach); a Department Head can drill into their own
  department's faculty but is forbidden from another department's; the Dean sees a college-wide
  dashboard; total units sum correctly across multiple sections (209 passing across Phase 1–5D)
- Live browser walkthrough against real seeded data: logged in as a Faculty member and confirmed
  "My Classes" showed exactly their two assigned sections with correct units, schedule, and
  section labels, and that switching to a semester with no assignments showed the empty state;
  logged in as a Department Head and confirmed the dashboard listed only their own department's
  Faculty and Department Head accounts (including a `0.00`-unit row for a faculty member with no
  assigned sections); clicked into a faculty member's drill-down and confirmed it matched that
  faculty's own "My Classes" view exactly; confirmed a direct URL to a faculty member outside the
  Department Head's department correctly returned "Forbidden"

**Phase 5 is now fully complete.** See `DATABASE_DESIGN.md`, `ASSUMPTIONS.md`, and
`ROLE_PERMISSIONS.md` for the full schema, design rationale, and permission matrix across all
four sub-phases.

## 10. Phase 6 Detailed Scope

**Goal:** give the college a working communications and coordination layer — announcements,
a shared calendar, meetings with tracked action items, assignable tasks, and an internal
request/approval workflow — plus a general document repository and a notification system tying
all of the above together. Broken into six sub-phases; each is built, tested, and verified before
the next begins, per the project's own phase-gate requirement.

### Sub-phase 6A: Announcements and Events — ✅ Complete

- `Announcement` and `Event` — both keyed on a nullable `department_id` (`null` = entire
  college, set = that department only) and `created_by`
- One new permission pair, `operations.manage`/`operations.view`, reused across both resources
  rather than minting `announcements.*`/`events.*` separately — matches the spec's own permission
  matrix, which scopes "Manage announcements/calendar" as a single capability
- Admin can post/edit/delete for any audience (entire college or any single department);
  Department Head can post/edit/delete for their own department only — enforced both in the
  Policy (`update()`/`delete()`) and in the FormRequest (`department_id` is silently forced to
  the actor's own department for anyone who isn't an Admin, the same field-level-restriction
  pattern used by `FacultyProfileRequest` in Phase 5A); Dean and Faculty are view-only
- `Announcement`/`Event::scopeVisibleTo()` — Admin/Dean unrestricted, everyone else sees
  college-wide items plus their own department's
- Events support an optional `end_at`, a `location`, and an "include past events" toggle
  (defaults to upcoming-only)
- Frontend: `Announcements/{Index,Create,Edit,Form}.tsx`, `Events/{Index,Create,Edit,Form}.tsx`;
  the "College Operations" nav entry is now a parent with live "Announcements"/"Events" children
  alongside still-disabled placeholders for Meetings/Tasks/Internal Requests
- Feature tests covering: Admin posting college-wide, a Department Head's post being forced to
  their own department regardless of submitted input, Dean/Faculty being forbidden from posting,
  visibility scoping (college-wide visible to everyone, department-scoped visible only to that
  department), a Department Head editing their own department's item but being forbidden from a
  college-wide one or another department's, deletion following the same scoping as editing, event
  date validation (`end_at` must not precede `start_at`), and the past/upcoming events filter
  (221 passing across Phase 1–6A)
- Live browser walkthrough: posted a college-wide announcement and an event as Admin; confirmed
  both render with working Edit/Delete controls; logged in as a Department Head, confirmed the
  create form shows no audience selector (just a "posted to your own department only" notice),
  posted a department-scoped announcement, confirmed it shows Edit/Delete for the Department Head
  while the Admin's college-wide announcement does not, and confirmed a direct URL to edit the
  college-wide announcement correctly returns "Forbidden"

### Sub-phase 6B: Meetings — ✅ Complete

- `Meeting` — same nullable-`department_id` college-wide/department-scoped shape as
  `Announcement`/`Event` (Phase 6A), reusing `operations.manage`/`operations.view`; Admin manages
  any audience, a Department Head only their own department, Dean/Faculty view-only
- `MeetingAttendee` — a simple invite record (`user_id`, `attended` boolean, `attended_at`,
  `invited_by`), mirroring the `CompetencyEvaluator` (Phase 4B) "assign a user to a parent
  record" shape; only whoever can manage the meeting can invite/remove attendees or mark
  attendance — attendance is organizer-recorded, not self-reported
- `MeetingActionItem` — description, assignee, due date, status, completion attribution
  (`completed_by`/`completed_at`), directly mirroring `StudentInterventionFollowup` (Phase 3D)
  field-for-field, with `meeting_id` in place of `student_id`
- New `ActionItemStatus` enum (pending/in_progress/completed/cancelled) — a deliberately
  generic name and shape, reused from `InterventionStatus` (Phase 3D) rather than duplicated,
  intended for Task (Phase 6C) to reuse as well
- `MeetingActionItemPolicy::update()`/`delete()` allow either the meeting's manager **or** the
  item's own assignee — mirroring `StudentInterventionFollowupPolicy` exactly, so an assignee can
  update their own item's status without being able to manage the meeting itself; only the
  manager (not just any assignee) can create new action items
- Frontend: `Meetings/{Index,Create,Edit,Form,Show}.tsx`; unlike Announcements/Events, Meetings
  has a `Show` page (nested Attendees and Action Items lists) since a meeting has genuine child
  records to manage, not just a title and body
- Feature tests covering: college-wide vs department-scoped scheduling and its Department-Head
  field-forcing, Dean/Faculty forbidden from scheduling, a Department Head forbidden from a
  college-wide or another department's meeting, a Faculty member forbidden from viewing another
  department's meeting, attendee invite/duplicate-prevention/attendance marking (manager-only),
  action item creation (manager-only) vs. status updates (assignee-or-manager), a Faculty member
  forbidden from updating an item assigned to someone else, and completion attribution being
  cleared when an item is reverted off `completed` (233 passing across Phase 1–6B)
- Live browser walkthrough: scheduled a department-scoped meeting as a Department Head (form
  correctly hid the audience selector), invited a Faculty member as an attendee, marked them
  attended, added an action item assigned to that Faculty member, then logged in as that Faculty
  member and confirmed they had no meeting-level Edit/Delete/Invite/Add controls but could still
  change their own action item's status to Completed — with the item correctly showing "Completed
  by Faculty 1 … on 2026-08-01" attribution afterward

### Sub-phase 6C: Tasks — ✅ Complete

- `Task` — a personal delegation tool, not department-scoped operational content: `title`,
  `description`, `assigned_to` (nullable, defaults to the creator if left blank), `due_date`,
  `status` (reusing the `ActionItemStatus` enum introduced in Phase 6B, as planned), completion
  attribution (`completed_by`/`completed_at`), `created_by`
- **No new permission.** Unlike every other Phase 6 resource, `TaskPolicy` doesn't check
  `operations.manage`/`.view` at all — any authenticated user may create a task and see their
  own; visibility is creator-or-assignee-or-Admin, not permission-gated. The "Tasks" nav entry
  has no `permission` field, the same as "Dashboard"/"Data Import"
- Field-level split on update, mirroring `FacultyProfileRequest` (Phase 5A): the creator or an
  Admin may edit every field; a plain assignee's request is restricted to `status`/`notes` only
  by `TaskRequest::rules()`, regardless of what else is submitted — verified by posting a
  title/reassignment change directly to the route as a non-owning assignee and confirming neither
  field changed
- The full `Edit` page itself is blocked (403) for a plain assignee at the controller level, not
  just hidden in the UI — their only path to changing anything is the inline status control on
  the Index list, the same pattern `MeetingActionItem` (Phase 6B) established
- `delete()` is stricter than `update()`: creator or Admin only, **not** the assignee — a
  deliberate divergence from `StudentInterventionFollowupPolicy`'s symmetric update/delete,
  because an assignee didn't create the task and deleting it affects the creator's own
  record-keeping, unlike updating its status
- `Task::scopeVisibleTo()` — Admin sees every task (oversight); everyone else sees only tasks
  they created or were assigned, a creator-or-assignee shape rather than the department-based
  shape every other Phase 5/6 model uses
- Frontend: `Tasks/{Index,Create,Edit,Form}.tsx`, with a status filter dropdown and an inline
  status control on the Index list (visible whenever the viewer is a participant); the "Tasks"
  nav entry (previously a disabled placeholder) is now live for every role
- Feature tests covering: any authenticated user (including Faculty) creating a task for
  themselves; a Dean assigning a task to someone else; a non-participant forbidden from
  viewing/updating/deleting; the creator fully editing a task; an assignee forbidden from the
  Edit page but able to update their own task's status; an assignee's attempt to change the
  title/assignee directly via the route being silently ignored while their status change still
  applies; an assignee forbidden from deleting (creator can); creator-or-assignee visibility
  scoping vs. Admin seeing everything; and the status filter (242 passing across Phase 1–6C)
- Live browser walkthrough: logged in as a Faculty member (no special permission) and confirmed
  they could reach Tasks and create one assigned to a colleague; confirmed the creator saw
  Edit/Delete controls; logged in as the assignee and confirmed they saw only an inline status
  control (no Edit/Delete), successfully changed the status via that control, and that a direct
  URL to the full Edit page correctly returned "Forbidden"

### Sub-phase 6D: Internal Requests — ✅ Complete

- `InternalRequest` — `requester_id`, `department_id` (snapshotted from the requester at
  submission), a free-text `type` (e.g. "Leave", "Resource", "Equipment" — open-ended, not an
  enum), `title`, `description`, `status` (new `InternalRequestStatus` enum:
  pending/approved/rejected/cancelled), and reviewer attribution (`reviewed_by`, `reviewed_at`,
  `remarks`)
- `RequestHistory` — an append-only audit row written automatically on every status change via
  model events (`InternalRequest::booted()`), exactly mirroring the `Student` →
  `student_status_histories` pattern from Phase 2C: `created()` logs the initial `null → pending`
  transition, `updating()` logs every subsequent transition whenever `status` is dirty, capturing
  `from_status`, `to_status`, `reason` (the reviewer's remarks), and `changed_by`. The caller
  never writes history rows directly — it's structurally impossible to change status without one
- No new permission — reuses `operations.manage`/`.view` a third time (Announcements/Events,
  Meetings, now Internal Requests). Anyone with `operations.view` (everyone) may submit a
  request; reviewing (approve/reject) requires `operations.manage`, in the reviewer's own
  department (or Admin, any department) — but never the requester's own department authority
  over their own request: `InternalRequestPolicy::review()` explicitly blocks self-review, even
  for an Admin or the requester's own Department Head
- `InternalRequestPolicy::view()`/`scopeVisibleTo()` is a four-tier shape distinct from every
  other Phase 6 resource: Admin/Dean see everything (oversight), Department Head sees their whole
  department's requests (their reviewing responsibility), but Faculty see only their **own**
  requests — not their department's, unlike the broadcast-content visibility Announcements/
  Events/Meetings give Faculty. A request is not something colleagues should see by default
- The requester may `cancel` their own request only while it's still `pending` — a withdrawal
  before anyone acts, not a delete; no edit page exists, since a submitted request is meant to be
  withdrawn and refiled, not corrected in place
- Frontend: `InternalRequests/{Index,Create}.tsx` — Create is a single inline form (no shared
  `Form.tsx`, since there's no Edit page to share it with); Index shows an inline Approve/Reject
  control (with optional remarks) for whoever can review, and a Cancel button for the requester
  while pending
- Feature tests covering: submission scoped to the requester's own department plus the automatic
  `request_histories` row; a Department Head approving their own department's request (with
  remarks recorded in the history's `reason`); a Department Head forbidden from another
  department's request; a Department Head forbidden from reviewing **their own** request
  (self-review guard); an already-decided request rejecting a second review attempt; Dean and
  Faculty both forbidden from reviewing anything; the requester cancelling a pending request but
  not an already-decided one; a non-requester forbidden from cancelling someone else's request;
  and the three-way visibility split (Faculty sees only their own, Department Head sees their
  whole department, Admin sees all) (251 passing across Phase 1–6D)
- Live browser walkthrough: submitted a leave request as a Faculty member and confirmed they saw
  a Cancel control (not Approve/Reject); logged in as their Department Head and confirmed they
  saw Approve/Reject controls, approved it with remarks, and confirmed the controls correctly
  disappeared once decided, with "Reviewed by ..." attribution shown; verified via `tinker` that
  both the initial `pending` and the `pending → approved` transition were correctly recorded in
  `request_histories`, including the reviewer's remarks as the `reason`

### Sub-phase 6E: Document Repository — ✅ Complete

- `DocumentCategory` — a small Admin-managed lookup list (name, description), the same shape and
  authorization as `GraduationRequirementTemplate` (Phase 4A): create/delete require the
  Administrator role specifically, not just `operations.manage`, so a Department Head can't
  invent their own category taxonomy. Deleting a category still referenced by a document is
  blocked with a friendly error rather than a DB constraint failure
- `Document` — reuses the exact nullable-`department_id` college-wide/department-scoped shape
  from Announcements/Events/Meetings (Phase 6A/6B) a fourth time, plus a `document_category_id`
  and `uploaded_by`
- `DocumentVersion` — the first version-history pattern in the schema. Each `Document` has one or
  more versions (`version_number`, file metadata, `notes`, uploader); `Document::latestVersion()`
  uses Eloquent's `latestOfMany('version_number')` to surface the current version without a
  separate "current version" pointer column. A document's first version is created in the same
  request as the document itself (one combined upload form); later versions are added via a
  separate "Upload New Version" action on the document's own page
- No new permission — reuses `operations.manage`/`.view` a fourth time for the documents
  themselves; `DocumentVersionPolicy::create()`/`delete()` are gated entirely through the parent
  `Document`'s own `update()` ability, the same "child action authorized via the parent" pattern
  `CompetencyEvaluator` (Phase 4B) and `MeetingAttendee`/`MeetingActionItem` (Phase 6B) already
  established
- A document's last remaining version cannot be deleted (the controller blocks it with a
  friendly error) — a document with zero versions would be a broken, undownloadable shell;
  removing the whole document is the correct action once nothing worth keeping remains
- File storage follows the exact private-disk, streamed-download pattern established by
  `StudentDocument` (Phase 2D) and `FacultyDocument` (Phase 5C) — `Storage::disk('local')`, never
  a public URL, served only through an authorized controller action
- Frontend: `DocumentCategories/Index.tsx` (list + inline add form, mirroring the Phase 5C
  `DocumentList` inline-form pattern), `Documents/{Index,Create,Edit,Show}.tsx` — Show carries the
  version history table and the upload-new-version form, the same "resource with genuine nested
  child data gets its own Show page" reasoning already applied to `Meeting` (Phase 6B). "Document
  Categories" was added under the existing "Settings" nav parent (alongside Departments/Programs/
  Graduation Requirements/Competency Framework — the same kind of admin-configurable lookup list);
  "Documents" itself is a live top-level nav entry
- Feature tests covering: Admin-only category creation (Department Head forbidden), a
  still-in-use category rejecting deletion, college-wide vs. department-scoped document upload
  and the Department-Head field-forcing, Dean/Faculty forbidden from uploading, visibility
  scoping (college-wide visible to everyone, department-scoped visible only to that department),
  a Department Head forbidden from editing a college-wide or another department's document,
  deleting a document removing its stored files, uploading a new version correctly incrementing
  `version_number`, the last-version deletion guard, and a Faculty member able to download but not
  upload a new version (262 passing across Phase 1–6E)
- Live browser walkthrough: created a "Policies" category as Admin; confirmed the Upload Document
  form renders correctly with the category/audience dropdowns and that the required `file` input
  blocks submission client-side when empty (file uploads can't be driven through the
  browser-automation tool, same limitation as Phase 5C); seeded a demo document + version via
  `tinker` and confirmed the Index list and Show page (version history, Edit/Delete, upload-new-
  version form) all render correctly; confirmed the download route returns 200 OK

### Sub-phase 6F: Notifications and Phase 6 Wrap-up — ✅ Complete

- Standard Laravel `notifications` table (UUID primary key, polymorphic `notifiable`, JSON
  `data`, `read_at`) — the "extended" language in earlier planning notes turned out unnecessary;
  every notification's contextual detail (title, message, target URL) fits in the default `data`
  payload, so no additional columns were added
- Four database-channel `Notification` classes, one per named trigger:
  `NewAnnouncementNotification`, `MeetingInvitationNotification`, `TaskAssignedNotification`,
  `InternalRequestStatusChangedNotification` — each a thin wrapper producing
  `{title, message, url}`
- Triggers wired directly into the four controllers at the exact moment each event happens:
  `AnnouncementController::store()` (notifies every active user in the announcement's audience,
  excluding the poster), `MeetingAttendeeController::store()` (notifies the invited attendee),
  `TaskController::store()`/`update()` (notifies the assignee on initial assignment and on
  reassignment, never on self-assignment), `InternalRequestController::review()` (notifies the
  requester on approval/rejection)
- A `notificationCenter` prop (`unread_count` + latest 8) shared globally via
  `HandleInertiaRequests`, powering a bell icon in `AppLayout`'s header on every page — deliberately
  named differently from the `notifications` prop `Notifications/Index.tsx` renders for itself
  (a full paginated list), after the two colliding under the same key caused the page-specific
  prop to silently overwrite the shared one
- `NotificationController` (`index`, `markRead`, `markAllRead`) plus a full `Notifications/Index.tsx`
  page; clicking a notification (from the bell or the full list) marks it read and navigates to
  its target — chained through `onFinish`, not fired-and-forgotten, after an early version's
  immediate `router.visit()` call was observed aborting the in-flight mark-as-read request
- Feature tests covering: college-wide vs. department-scoped announcement notification targeting,
  meeting invitation notifications, task-assignment notifications on both creation and
  reassignment (and correctly *not* firing on self-assignment), internal-request-decision
  notifications, a user only able to mark their own notifications read, "mark all read," and the
  notifications index page (271 passing across Phase 1–6F)
- Live browser walkthrough: posted a college-wide announcement as Admin, logged in as a Faculty
  member and confirmed the bell showed an unread badge, opened the dropdown, clicked the
  notification, and confirmed it navigated to Announcements *and* the badge cleared — catching
  and fixing two real bugs along the way (the aborted-PATCH race condition and the prop-name
  collision that crashed `Notifications/Index.tsx`) before both were confirmed working

**Phase 6 (College Operations) is now fully complete.** See `DATABASE_DESIGN.md`,
`ASSUMPTIONS.md`, and `ROLE_PERMISSIONS.md` for the full schema, design rationale, and permission
matrix across all six sub-phases.

## 11. Phase 7 Detailed Scope

**Goal:** give the department a system of record for the two remaining spec areas it doesn't
yet have anywhere — agriculture support activity (research/extension) and the physical assets
that support teaching and research (facilities/equipment) — following the same phase-gate
discipline as Phases 1–6. Four independently demoable sub-phases:

### Sub-phase 7A: Research — ✅ Complete

Research project tracking: projects (title, description, status, department, funding source,
timeline), the project's member roster (with one lead), and its outputs (publications,
presentations, patents, etc.). Faculty manage their own projects; Department Head is
view-only for their department per the spec's explicit permission row (a deliberate departure
from Phase 6's more liberal `operations.manage` grant to Department Head — see
`ASSUMPTIONS.md`); Dean and Administrator see everything, Administrator can manage everything.

- `ResearchProjectStatus` enum (proposed/ongoing/completed/cancelled) — a new enum rather than
  reusing `InternalRequestStatus` or `ActionItemStatus`, since the meanings differ even where the
  shape looks similar
- `research_projects` (required `department_id` — always someone's own department, no
  college-wide broadcast case), `research_members` (pivot-shaped, `is_lead` boolean, unique per
  project+user), `research_outputs` (free-text `type`, soft-deletable)
- New `research-extension.manage`/`research-extension.view` permission pair, seeded
  Administrator-only for `.manage` — deliberately shared with Extension (Phase 7B), since the
  spec gives both an identical permission row
- `ResearchProjectPolicy`: `viewAny`/`view` gate on `.view` (department-scoped for Dept.
  Head/Faculty, unrestricted for Admin/Dean); `create()` passes for `.manage` **or** any Faculty
  role; `update()`/`delete()` pass for `.manage` **or** project leadership
  (`research_members.is_lead`) — Department Head's view permission alone does not satisfy either
  check, honoring the spec's explicit view-only row literally rather than as free design judgment
- `ResearchMemberPolicy`/`ResearchOutputPolicy` have no rules of their own — both delegate
  `create()`/`delete()` to `ResearchProjectPolicy::update()` on the parent project, the
  child-authorized-via-parent pattern already used for `CompetencyEvaluator` (Phase 4B) and
  `MeetingAttendee`/`MeetingActionItem` (Phase 6B)
- `ResearchProjectController::store()` auto-adds the creator as the project's lead member inside
  the same DB transaction, so a brand-new project is immediately editable by its creator
- Frontend: `Research/{Index,Create,Edit,Show}.tsx` plus `MemberList.tsx`/`OutputList.tsx` child
  components, mirroring `Meetings/{Index,Create,Edit,Show}.tsx` and its
  `AttendeeList.tsx`/`ActionItemList.tsx` pattern exactly; `navigation.ts`'s `Research` entry now
  points at `research-projects.index`
- Feature tests covering: Faculty self-create with auto-lead, Admin cross-department create,
  Department Head and Dean forbidden from creating, only the project lead or Admin able to
  update/delete (a non-lead member and a Department Head both rejected), department-scoped
  visibility (Admin/Dean unrestricted, Dept. Head/Faculty own-department only), member add/remove
  gated to the lead, duplicate-member rejection, output add/remove gated to the lead (282 passing
  across Phase 1–7A)
- Live browser walkthrough: logged in as Faculty, created a project (confirmed no department
  picker and automatic lead assignment), added a second member and an output from the Show page;
  logged in as the department's Head and confirmed the project list showed no "New Project"
  button, the Show page rendered read-only (no Edit/Delete, no Add controls, no Remove buttons),
  and `/research-projects/create` returned a 403; logged in as Admin and confirmed the
  Create/Edit forms both show a Department picker (Admin has none of their own) alongside the
  Status field on Edit

**Bug caught during verification:** `Route::resource('research-projects', ...)` defaults to a
snake_case `{research_project}` route parameter, but every custom nested route
(`members`/`outputs`) and the FormRequests were written against camelCase `{researchProject}` —
matching the convention every other kebab-named resource in this app uses (`class-sections` →
`{classSection}`, etc.) via an explicit `->parameters([...])` call. Without it, `research_project`
and `researchProject` are different keys, so `ResearchProjectRequest::authorize()`'s
`$this->route('researchProject')` lookup silently returned `null` on the base resource's
show/update/destroy/edit routes — misrouting every `update` authorization check into the
`create()` branch instead. Caught by the Pest suite (a non-lead member's update was wrongly
allowed), fixed by adding `->parameters(['research-projects' => 'researchProject'])` to the
resource route registration.

### Sub-phase 7B: Extension — ✅ Complete

Extension project tracking: projects, member roster, activities conducted, and beneficiaries
reached — the same overall shape as Research (project → members → child records), reusing the
`research-extension.manage`/`.view` permission pair since the spec gives Research and Extension
an identical permission row.

- `ExtensionProjectStatus` enum — new, not a reuse of `ResearchProjectStatus`, despite identical
  case values; kept separate for the same domain-naming reason `ActionItemStatus` was kept
  separate from `InterventionStatus` (Phase 6B)
- `extension_projects` (structurally identical to `research_projects`: required
  `department_id`, status, timeline, funding source, soft-deletable), `extension_members`
  (pivot-shaped, one lead per project), `extension_activities` (free-text `activity_type`,
  soft-deletable), `extension_beneficiaries` (free-text `beneficiary_type`, nullable `count`,
  soft-deletable) — the last two are both flat children of the project, matching the one-level
  nesting used everywhere else in this schema
- No new permission — reuses `research-extension.manage`/`.view`, minted in Phase 7A
  specifically anticipating this reuse
- `ExtensionProjectPolicy` is structurally identical to `ResearchProjectPolicy`; the three child
  policies (`ExtensionMemberPolicy`, `ExtensionActivityPolicy`, `ExtensionBeneficiaryPolicy`)
  delegate `create()`/`delete()` to `ExtensionProjectPolicy::update()` with no rules of their own
- `ExtensionProjectController::store()` auto-adds the creator as lead member in the same DB
  transaction, exactly as `ResearchProjectController::store()` does
- Frontend: `Extension/{Index,Create,Edit,Show}.tsx` plus `MemberList.tsx` (near-identical to
  Research's), `ActivityList.tsx`, and `BeneficiaryList.tsx`; `navigation.ts`'s
  `Extension Services` entry now points at `extension-projects.index`
- **Route parameter naming got the explicit `->parameters(['extension-projects' =>
  'extensionProject'])` from the very first draft** — the Phase 7A bug (default snake_case
  `{research_project}` silently breaking `FormRequest::authorize()`'s camelCase route lookup)
  informed this from the start, so 7B's route registration never had the bug to catch
- Feature tests covering: Faculty self-create with auto-lead, Admin cross-department create,
  Department Head and Dean forbidden from creating, only the project lead or Admin able to
  update/delete, department-scoped visibility, member/activity/beneficiary add-remove gated to
  the lead, duplicate-member rejection (295 passing across Phase 1–7B)
- Live browser walkthrough: logged in as Faculty, created a project (confirmed auto-lead), added
  an activity and a beneficiary from the Show page; logged in as the department's Head and
  confirmed the Show page rendered fully read-only and `/extension-projects/create` returned a
  403; logged in as Admin and confirmed the Edit form shows both the Department picker and the
  Status field pre-filled

### Sub-phase 7C: Facilities — ✅ Complete

Full facilities expansion (labs, farms, greenhouses, field locations) as a proper resource with
its own CRUD, plus the migration that backfills `class_schedules.room` (free text since Phase 2)
into a real `facility_id` foreign key, per the commitment made in `DATABASE_DESIGN.md` when that
column was first introduced.

- `facilities` table: name (unique), free-text `type`, nullable `department_id` (shared vs.
  department-owned — the Announcement/Event/Meeting/Document shape, not Research/Extension's
  always-owned shape), location, capacity, description, `is_active` boolean, nullable
  `created_by` (the only nullable `created_by` in the schema, for data-migration-backfilled rows)
- No new permission — `Facility` reuses `operations.manage`/`operations.view`; `FacilityPolicy`
  is structurally identical to `AnnouncementPolicy`/`MeetingPolicy`
- `FacilityController` follows the "simple lookup resource" shape (`index`/`create`/`store`/
  `edit`/`update`/`destroy`, no `show`) — same as Announcements/Events, since a facility has no
  child records of its own yet
- **The `class_schedules.room` → `facility_id` backfill**: one migration adds the nullable FK,
  creates one `Classroom`-type `Facility` per distinct historical `room` value (department null,
  creator null), repoints every matching schedule, then drops `room` — all in a single `up()` so
  the free-text column and the FK are never both live at once. `ClassSchedule` model,
  `ClassScheduleRequest`, `ClassSectionController::edit()`, `FacultyWorkloadService`, and
  `EnrollmentSeeder` were all updated in the same sub-phase; the Class Sections Edit page's
  schedule form now shows a Facility dropdown instead of a free-text Room input
- Frontend: `Facilities/{Index,Create,Edit}.tsx` (mirrors Announcements — no Show page);
  `navigation.ts`'s "Facilities and Equipment" entry became a parent with two children —
  "Facilities" (now routed) and "Equipment" (still `plannedPhase: 7`, anticipating 7D the same
  way Curricula/Courses and Enrollment already use the `children` shape)
- Feature tests covering: Admin college-wide facility registration, Department Head own-dept-only
  registration (forced regardless of input), Dean and Faculty forbidden from registering,
  shared-vs-department visibility scoping, Department Head forbidden from editing a shared or
  another department's facility, unique-name validation, and a class schedule successfully
  assigned a `facility_id` (302 passing across Phase 1–7C)
- Live browser walkthrough: confirmed the seeded "Room 101" facility (created by the backfill)
  appears correctly in the Facilities index and in the class-section schedule Facility dropdown;
  registered a new "Agronomy Laboratory 1" facility as Admin; added a new schedule entry to an
  existing class section using that facility and confirmed it displays correctly in the schedule
  table; confirmed "Facilities" now renders as a live nested nav link under "Facilities and
  Equipment" while "Equipment" still renders disabled

### Sub-phase 7D: Equipment — ✅ Complete

Equipment inventory, borrowing/return workflow, and maintenance records, scoped to the
facilities introduced in 7C.

- `EquipmentStatus` enum (available/borrowed/under_maintenance/retired) — a persisted status
  column driven directly by workflow actions, the same shape as `Task`/`MeetingActionItem`
  status, not a computed aggregate
- `equipment` (independent nullable `department_id` and `facility_id` — ownership vs. current
  location are different facts), `equipment_borrowings` and `equipment_returns` as two separate
  append-only tables (honoring the spec's literal naming — a borrowing is written once, a return
  is a distinct later event), `equipment_maintenance` as one table with nullable `completed_at`
  meaning "still ongoing"
- **No `accountabilities` table** — deliberately not built. "Who is accountable for what
  equipment" is computed on demand (`EquipmentBorrowing::whereDoesntHave('return')`), the same
  "computed on demand, persisted only where a workflow needs it" philosophy already applied to
  `FacultyWorkloadService` (Phase 5D). A full `Equipment/Accountability.tsx` report page still
  exists — only the redundant storage was cut
- No new permission — `Equipment` reuses `operations.manage`/`operations.view`, the same
  free-design-judgment precedent as `Facility` (Phase 7C), since the spec has no
  equipment-specific permission row
- `EquipmentPolicy` mirrors `FacilityPolicy` exactly; `EquipmentBorrowingPolicy`,
  `EquipmentReturnPolicy`, `EquipmentMaintenancePolicy` all delegate to
  `EquipmentPolicy::update()` — no rules of their own. Business rules (equipment must be
  `available` to borrow or send for maintenance; a borrowing can't be returned twice) live in
  FormRequests/controllers, not the Policy layer
- `EquipmentBorrowingController`/`EquipmentReturnController`/`EquipmentMaintenanceController`
  each flip `equipment.status` as a side effect of their action, inside a DB transaction with the
  history row they create
- `EquipmentAccountabilityController` — a dedicated read-only report controller/page listing
  every currently-outstanding borrowing, department-scoped by the same `visibleTo()` rule as the
  equipment index; its route is registered *before* `Route::resource('equipment', ...)` so
  `/equipment/accountability` isn't swallowed by the `{equipment}` show-route wildcard (the same
  ordering precedent already used for `graduation-candidates/report/batch`)
- **Model naming pitfall caught during verification:** Eloquent's default table-name guess for
  `EquipmentMaintenance` is the plural `equipment_maintenances`, but the migration (matching the
  spec's literal singular table name) creates `equipment_maintenance`. Fixed with an explicit
  `protected $table = 'equipment_maintenance'` on the model. Caught immediately by the Pest suite
  (an `SQLSTATE... no such table` error) before it ever reached manual verification
- Frontend: `Equipment/{Index,Create,Edit,Show,Accountability}.tsx`, with Borrow/Return/
  Maintenance forms and a "Mark Complete" action embedded directly in `Show.tsx` (local
  components in the same file, the same pattern `ClassSections/Edit.tsx`'s `AddScheduleForm`
  already established) rather than split into separate child-component files, since each is a
  single-purpose action form, not a repeated CRUD list like Research/Extension's members/outputs.
  `navigation.ts`'s `Equipment` entry now points at `equipment.index`
- Feature tests covering: Admin/Department-Head registration scoping, Dean/Faculty forbidden,
  visibility scoping, the full borrow→return cycle (including "already borrowed" and
  "already returned" rejections), the full maintenance report→complete cycle (including
  "can't maintain borrowed equipment"), and the accountability report's department-scoped,
  outstanding-only contents (314 passing across Phase 1–7D)
- Live browser walkthrough as Admin: registered equipment, recorded a borrowing (status →
  Borrowed, Return form appeared, Borrow/Maintenance forms correctly hidden), confirmed the item
  appeared on the Accountability report, recorded its return (status → Available, item dropped
  off the Accountability report), reported maintenance (status → Under Maintenance), and marked
  it complete (status → Available again) — the full equipment lifecycle in one continuous pass

**Phase 7 (Agriculture Support Modules) is now fully complete** across all four sub-phases
(7A–7D). See `DATABASE_DESIGN.md`, `ASSUMPTIONS.md`, and `ROLE_PERMISSIONS.md` for the full
schema, design rationale, and permission matrix.

## 12. Phase 8 Detailed Scope

**Goal:** turn the seven phases of recorded data into decision-useful information — dashboards,
printable/exportable reports, a backup safety net, and the hardening/documentation pass that
makes the system genuinely deployable, not just feature-complete. Unlike Phases 2–7, Phase 8
introduces no new domain tables (see `DATABASE_DESIGN.md`) — everything it builds reads from
data every prior phase already produces. Four sub-phases:

### Sub-phase 8A: Role-Based Dashboards — ✅ Complete

Replace the Phase 1 placeholder `DashboardController` (student/program counts only) with real,
distinct widgets per role, using Chart.js (`react-chartjs-2`, added this sub-phase per the
Technology Stack table) for the handful of visualizations that warrant a chart over a stat card.
Administrator and Dean see college-wide KPIs; Department Head sees the same shape scoped to their
own department; Faculty sees a personal view (their sections, advisees, tasks, and own research/
extension work). Charts show current-state distributions (e.g. student status breakdown, at-risk
counts by department), not time-series trends — no new snapshot table is introduced, honoring the
Phase 3 deferral note in `ASSUMPTIONS.md` that a GWA-trend-over-time chart is genuinely Phase 8
work only once something needs point-in-time history, which this sub-phase's scope does not.

- `DashboardController::__invoke()` branches on role (`match (true) { $user->hasRole(...) => ... }`)
  into four private builders — `administrator()`, `dean()`, `departmentHead()`, `faculty()` — each
  returning its own stat-card list and chart set. No new permission or route gate: `/dashboard`
  has always been open to any authenticated user, and Phase 8A's authorization is entirely
  "query different things per role" using each model's existing `scopeVisibleTo()` or a direct
  `department_id`/`adviser_id`/`assigned_to` filter
- A shared `statusBreakdown()` helper groups a query by an enum-backed status column and labels
  every case (including zero-count ones) so charts never silently drop a category; a separate
  `atRiskByDepartment()` helper aggregates `ProgressAlert` (unresolved, i.e. `resolved_at IS
  NULL`) counts per department in one query, avoiding N+1
- Admin sees 8 stat cards + 4 charts (students by status, graduation pipeline, at-risk by
  department, equipment by status); Dean sees a lighter college-wide view (4 stat cards + 3
  charts, no user-management-adjacent numbers); Department Head sees the same shape as Admin
  scoped to their own department (7 stat cards + 2 charts); Faculty sees 6 personal stat cards
  and no charts — a single person's data is too small a sample for a distribution chart to say
  anything a number doesn't already say faster
- `react-chartjs-2` + `chart.js` installed via npm; `Dashboard.tsx` registers only the Chart.js
  modules actually used (`CategoryScale`, `LinearScale`, `BarElement`, `Tooltip`, `Legend`) and
  renders every chart as a bar chart for visual consistency, with a "No data yet" fallback when
  every value is zero rather than an empty/broken canvas
- Feature tests covering: Admin sees all-department aggregates, Dean gets the college-wide role
  tag, Department Head's student count and chart are scoped to their own department only (a
  same-count student in another department is excluded), a Faculty member's "My Advisees" stat
  reflects only students where they are the assigned adviser, and all four roles can load
  `/dashboard` without error (319 passing across Phase 1–8A)
- Live browser walkthrough as Admin, Department Head, and Faculty: confirmed each role's stat
  cards and chart set matched the query-scoped data (6 active students in the Department Head's
  own department, 2 class sections for the Faculty member this semester), confirmed the "No data
  yet" fallback renders correctly for charts with zero data, and confirmed exactly one `<canvas>`
  element was present on the Admin dashboard (the one chart — Students by Status — that had real
  data), with no console errors

### Sub-phase 8B: Reports (PDF/Excel) — ✅ Complete

The "Generate authorized college reports" capability (college-level for Admin/Dean, department-
level for Department Head, per the spec's explicit permission row). Five canned reports
(Enrollment Summary, Academic Performance/Grade Distribution, At-Risk & Progress Summary, Faculty
Workload Summary, Graduation Pipeline Summary), each exportable to PDF (`barryvdh/laravel-dompdf`,
already installed since Phase 4D) and Excel (`maatwebsite/excel`, already installed since Phase
2G) — no new packages needed. No new tables — every report is computed on demand from data
Phases 2–5 already produce.

- `App\Enums\ReportType` (backed enum, one case per report) drives implicit route-model binding
  on `Route::get('reports/{type}', ...)`, each case's `label()`/`description()` for the index
  cards, and `availableFilters()` for which filter inputs the frontend renders per report
- `ReportService::generate()` is the single method both the Inertia preview and the PDF/Excel
  export call — one method per report type internally (`enrollment()`, `grades()`, `atRisk()`,
  `facultyWorkload()`, `graduationPipeline()`), each returning a plain `{headings, rows}` array so
  the preview table and the exported file can never disagree. `departmentIdFor()` centralizes the
  college-vs-department scoping: a Department Head is forced onto their own `department_id`; Admin/
  Dean may optionally filter by department or see the whole college
- Academic Performance counts only `Finalized`/`Locked` grades (settled records, not in-progress
  encoding); Graduation Pipeline Summary shows every pipeline status for oversight, complementing
  (not duplicating) the Phase 4D ceremony-list PDF which is Approved/Graduated only
- One `reports.view` permission (Admin/Dean/Department Head; explicitly withheld from Faculty per
  the spec) — no dedicated Policy class, mirroring `AuditLogController` (Phase 1); authorization is
  `Route::middleware('permission:reports.view')` plus the service-layer query scoping described
  above
- `App\Exports\ReportExport` (generic `FromArray`/`WithHeadings`) and
  `resources/views/pdf/report.blade.php` (generic Blade template, styled after the Phase 4D
  graduation-batch-report template) are both reused across all 5 report types — one exporter, one
  PDF view, parameterized by `{headings, rows}`, instead of five near-identical copies of each
- `Reports/Index.tsx` lists all 5 report types as clickable cards; `Reports/Show.tsx` is a
  single generic viewer — semester/department filter bar (rendered conditionally per
  `availableFilters`/role), a data table over arbitrary `headings`/`rows`, and Download PDF/Excel
  links built via Ziggy's `route()` helper
- Feature tests covering: index access by role (Admin/Dean/Department Head allowed, Faculty
  forbidden), every report type renders for an authorized role, an unknown report type 404s
  (implicit enum route binding), Faculty is forbidden from every report route including PDF/Excel,
  the enrollment report is scoped to a Department Head's own department, an Admin can narrow by
  department filter, PDF export streams `application/pdf`, Excel export streams a downloadable
  `.xlsx` (327 passing across Phase 1–8B)
- Live browser walkthrough as Admin: all 5 report types render correct headings/rows against real
  seeded data (Faculty Workload showed 8 faculty rows with correct section/unit totals; At-Risk
  and Graduation Pipeline correctly showed empty states since no seeded records existed for those
  in the dev DB); semester and department filters both re-query correctly; PDF and Excel downloads
  confirmed via direct fetch (`200`, correct `Content-Type`/`Content-Disposition`). As Department
  Head: Enrollment Summary auto-scoped to "Department of Crop Science" with no department picker
  shown. As Faculty: `/reports` returns a `403 Forbidden` page, and the "Reports" nav item is
  correctly absent from the sidebar. One bug was found and fixed during this walkthrough — see
  below.
- **Bug fixed during verification:** `ReportService::scopeDescription()` originally prefixed the
  department name with `'Department of '`, but the seeded `Department.name` values are already
  full names (e.g. "Department of Crop Science"), producing a doubled "Department of Department of
  Crop Science" on screen. Fixed by returning the department name directly; the two affected
  `ReportsTest` assertions were updated to match.

### Sub-phase 8C: Backup and Restore — ✅ Complete

The Admin-only "Perform backup and restore" capability: trigger a database backup, list existing
backups, download one, and restore from one. No new tables — backups are plain `.sql` files under
`storage/app/private/backups/`, listed by reading the directory directly (same "compute on demand"
discipline as the rest of Phase 8); who triggered a backup or restore is logged via the existing
`spatie/laravel-activitylog` infrastructure, visible in the Phase 1 Audit Logs viewer.

- `App\Services\BackupService` shells out to `mysqldump`/`mysql` via Laravel's `Process` facade —
  `create()` runs `mysqldump --single-transaction --routines --events` and writes the output
  straight to disk; `restore()` pipes an existing backup's contents into `mysql` via stdin;
  `list()`/`download()` read the backup directory directly. Binary paths are configurable via
  `DB_MYSQLDUMP_PATH`/`DB_MYSQL_PATH` (`config/backup.php`), defaulting to `mysqldump`/`mysql` on
  PATH, documented for XAMPP installs where they aren't
- Filenames follow one closed pattern (`ca-apoms_YYYY-MM-DD_HHmmss.sql`), enforced both by the
  route (`Route::where()`) and again inside `BackupService::assertValidFilename()` — a filename
  is never accepted as free-form input anywhere in the download/restore path, closing off path
  traversal by construction
- One `backups.manage` permission, Admin-only with **no partial grant for any other role** — the
  strictest tier in the whole permission matrix, matching the spec's row exactly (✅ Admin, ⛔
  everyone else). No dedicated Policy class, same reasoning as `AuditLogController`/
  `ReportController`: route-level `permission:backups.manage` middleware is sufficient since there
  is no per-backup ownership to adjudicate
- `Backups/Index.tsx`: a table of existing backups (filename, size, created date) with a "Create
  Backup" button and, per row, a "Download" link and a "Restore" button gated behind a confirm
  modal that names the exact file and warns the action is irreversible before proceeding
- Feature tests (using Laravel's `Process::fake()`, so no real `mysqldump`/`mysql` binary is
  needed in CI) covering: only Admin can view the index (Dean/Department Head/Faculty forbidden);
  a triggered backup shells out to `mysqldump` and writes a file whose contents match the faked
  process output, with a matching `backups` activity-log entry; a failed `mysqldump` writes no
  file and flashes an error; the index lists an existing backup's filename/size/created date; only
  Admin can download; a malformed filename never reaches the controller (404 at the route layer);
  a restore pipes the backup's file contents into `mysql` via stdin and logs a `Restored database`
  activity entry; a failed restore flashes an error and is still logged; a non-admin is forbidden
  from both triggering a backup and restoring one (337 passing across Phase 1–8C)
- Live browser walkthrough as Admin: triggered two real backups against the actual seeded MySQL
  database (not faked) — each produced a ~204 KB `.sql` file, listed correctly with size/created
  date; downloaded a backup and confirmed `Content-Type: application/x-sql` and the correct
  `Content-Disposition`; restored from a backup through the real confirm-modal flow, confirmed via
  the `backups` activity log entry and confirmed the app (dashboard, login) still functioned
  normally afterward with data intact. Confirmed Dean and Faculty both get a live `403 Forbidden`
  on `/backups` (unlike Phase 8B's Reports, where Dean has college-level access — here Dean gets
  none, matching the spec's stricter row), and confirmed "Backup and Restore" is absent from both
  roles' sidebar nav.
- **Real bug found and fixed during this walkthrough** (environment-specific, not a logic bug):
  `mysqldump` reliably failed with `Can't create TCP/IP socket (10106)` only when invoked through
  `php artisan serve` (not via `php artisan tinker`). Root cause: Symfony Process's environment
  derivation only fully inherits the parent process's env on the `cli`/`phpdbg`/`embed` SAPIs;
  `php artisan serve` runs under `cli-server`, which instead intersects `getenv()` with `$_SERVER`
  — silently dropping a variable `mysqldump.exe`'s Winsock initialization needs on this Windows
  host. Fixed by having `BackupService::mysqlEnv()` explicitly pass the full `getenv()` to every
  `Process::env()` call, which would affect any Windows deployment run via `php artisan serve` (the
  documented local dev workflow), not just this machine. See `ASSUMPTIONS.md` for the full
  diagnostic trail.

### Sub-phase 8D: Hardening and Finalization — ✅ Complete

A performance pass (N+1/eager-loading audit across all controllers), a security review
(authorization coverage, mass-assignment guards), a basic accessibility pass on key pages, one
final full regression test run, and writing the four documentation files deferred since Phase 1
specifically for this moment: `DEPLOYMENT.md`, `BACKUP_RESTORE.md`, `USER_GUIDE.md`, and
`API_DOCUMENTATION.md`. This closes out Phase 8 and the project's original 8-phase build plan.

- **Three independent audits**, each run as a full pass across the entire codebase (75
  controllers, 70 models, 45 policies, 61 FormRequests) before any fix was applied: an
  N+1/eager-loading audit, an authorization-coverage audit, and a mass-assignment-guard audit.
  Full findings and reasoning for what was fixed vs. deliberately deferred are in
  `ASSUMPTIONS.md`'s Phase 8D section — summarized here:
- **2 real, exploitable security gaps fixed**: `ImportController::show()`/`errors()` had no
  authorization check at all (any authenticated user could read another department's import
  batches, including raw per-row PII in `errors()`) — fixed with the same per-type permission
  check `store()` already had, plus scoping `index()`'s batch list; `StudentRequest` never forced
  `department_id` to the caller's own department for non-Administrators, letting a Department
  Head plant or move a student into another department via a crafted request — fixed with the
  same `prepareForValidation()` forcing pattern already used by every sibling FormRequest with a
  `department_id` field. Two related LOW-severity, currently-inert consistency gaps
  (`CurriculumCourseController` parent/child ID mismatch, `CurriculumPolicy` missing department
  scoping) were hardened the same way for consistency, plus the same defensive
  `department_id`-forcing added to `CourseRequest`/`ProgramRequest`.
- **2 of 4 found N+1 patterns fixed**: `FacultyWorkloadService::summaryFor()` re-queried once per
  faculty member (an M-query N+1 hit on every Faculty Workload dashboard/report load) — rewritten
  to one batched, grouped query; `ReportService::facultyWorkload()` was missing one eager-loaded
  relation feeding into it. The other 2 (`GraduationCandidateService::identifyEligibleStudents()`,
  `AtRiskController::index()`) sit inside already-shipped Phase 3/4 business logic and were left
  as a documented, deliberate limitation rather than risking a mid-hardening-pass rewrite of
  tested code — see `ASSUMPTIONS.md` for the full reasoning.
- **Accessibility pass** on one representative page per UI pattern (Dashboard, a list page, a
  create form, a modal) rather than an exhaustive sweep of every page: fixed unlabeled Chart.js
  canvases (Dashboard), added `aria-current`/accessible names to the shared `Pagination`
  component (benefits every list page in the app at once), fixed unlabeled search/filter inputs
  and missing `<th scope="col">` on `Students/Index.tsx`, and fixed a systemic missing-`htmlFor`
  bug found on 2.something of 18 `Form.tsx` files (`Students/Form.tsx`'s ~30 fields,
  `ClassSections/Form.tsx`'s 6 fields, 2 checkbox-group labels in `Courses/Form.tsx`) — the other
  15 `Form.tsx` files already correctly paired every label with its input. The shared `Modal`
  component (Headless UI `Dialog`) was checked and already handles focus/`aria-modal` correctly.
- **Final regression**: 341 Pest tests passing (up from 337 at the close of Phase 8C), Pint
  clean, `tsc --noEmit` clean, `npm run build` clean, `migrate:fresh --seed` clean.
- **Documentation**: `DEPLOYMENT.md` (production deployment guide, including an explicit warning
  — backed by the Phase 8C `cli-server` SAPI bug — never to run `php artisan serve` in
  production), `BACKUP_RESTORE.md` (operational guide for Phase 8C's Backup and Restore module),
  `USER_GUIDE.md` (role-by-role workflow guide for all four roles), and `API_DOCUMENTATION.md`
  (the full Inertia route surface, organized by module, with an explicit note that this is not a
  public REST API).

## 13. Definition of Done (applied every phase)

A module is done only when: migrations exist; models/relationships work; validation
(Form Requests) is implemented; authorization (Policies/Gates/scopes) is enforced; backend
CRUD works; frontend Inertia pages are complete; search/filter/sort work where applicable;
empty/error states are handled; audit logging is wired where required; automated tests pass;
docs are updated; the module works against realistic seeded data; no console/server errors.

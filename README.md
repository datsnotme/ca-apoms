# CA-APOMS

**College of Agriculture Academic Progress and Operations Management System**

A Laravel + Inertia (React/TypeScript) + MySQL application that centralizes student
academic records, progress monitoring, faculty profiles, graduating-student evaluation,
faculty workload, and general college operations for a College of Agriculture.

> Development status: **All 8 phases complete.** Phase 1 (Foundation), Phase 2 (Core Academic
> Management), Phase 3 (Student Progress), Phase 4 (Graduating Student Evaluation), Phase 5
> (Faculty Management), Phase 6 (College Operations), Phase 7 (Agriculture Support Modules —
> Research, Extension, Facilities, Equipment), and Phase 8 (Analytics and Finalization — Role-Based
> Dashboards, Reports, Backup and Restore, Hardening and Finalization). See `PROJECT_PLAN.md` for
> the full phase-by-phase build history, and `DEPLOYMENT.md` before deploying this anywhere beyond
> local development.

## Purpose

Replace manual, spreadsheet-based tracking of student academic progress and college
operations with a single, role-aware, auditable system. See `PROJECT_PLAN.md` for the
complete module list and `DATABASE_DESIGN.md` for the schema.

## Features (Phase 1)

- Secure authentication: login, account lockout after 5 failed attempts (15 min), forced
  password change for newly created accounts, password reset, disabled-account rejection.
- Role-based + permission-based authorization (College Administrator, College Dean,
  Department Head, Faculty Member) enforced via Policies, route middleware, and
  department-aware query scopes — not just hidden navigation.
- College / Department / Program management.
- Academic Year / Semester management.
- User account management (admin-only): create, edit, deactivate, archive/restore, role and
  department assignment.
- Audit log viewer with role-appropriate filtering.
- Collapsible sidebar showing the full planned module list (unbuilt modules are visibly
  disabled, not hidden or 404ing) per the UI spec.

## Features (Phase 2)

- Curricula and Courses: course catalog with prerequisites/corequisites, per-curriculum
  checklist builder grouped by year level and semester.
- Students: full profile CRUD, guardian/emergency contact, addresses, classification/status
  with an automatic status-history audit trail.
- Student Documents: category-tagged uploads (never publicly served), Admin verify/reject
  workflow, Department-Head read access within their own department.
- Class Sections and Enrollment: section capacity/schedule/primary-faculty setup, per-semester
  student enrollment with add/drop and duplicate/capacity checks.
- Grade Management: draft → submit → review → finalize workflow per class section, with every
  individual grade change logged and a targeted post-finalization correction path.
- Excel Imports: downloadable templates and row-by-row validated bulk import (with a
  downloadable per-row error report) for Students, Courses, Curriculum Courses, Enrollment,
  and Grades.

## Features (Phase 3)

- Progress Computation: per-student curriculum checklist (completed/failed/in-progress/pending/
  etc.), computed live from actual grades rather than cached, plus GWA and curriculum-completion
  percentage.
- Deficiency Detection: required courses whose expected year level has already passed and
  aren't complete are automatically flagged, with a retake/substitution/waiver resolution
  workflow.
- Advising: adviser assignment history, session notes with recommendations and a follow-up
  flag, all surfaced on the student's Progress page.
- At-Risk Monitoring: automatic alerts for multiple deficiencies, a low GWA, or a concerning
  enrollment status, with department/advisee-scoped visibility and acknowledgment tracking.
- Intervention Follow-ups: action items assignable to any faculty member, tracked through to
  completion, optionally linked back to the advising session or alert that prompted them.
- A browser-print affordance on the consolidated Progress page (checklist + GWA + deficiencies
  + alerts + advising + follow-ups) — the "progress report" deliverable, without introducing PDF
  generation ahead of Phase 4.

## Features (Phase 4A)

- Graduation Candidate Identification: eligible students (100% curriculum completion, zero
  unresolved deficiencies) surfaced automatically for nomination.
- Nomination snapshots GWA, completion %, and deficiency count at the moment of nomination —
  the graduation record does not silently drift if a grade is edited later.
- Graduation Requirement Templates: Admin-configurable checklist items, applicable to a single
  program or to all programs.
- Per-candidate requirement checklist with satisfy/waive/reset actions, recording who and when.

## Features (Phase 4B)

- Competency Framework: Admin-configurable rating categories and indicators evaluators score a
  candidate against.
- Evaluator Assignment: Admin assigns Faculty (same department as the candidate) as competency
  evaluators; assigning the first evaluator moves the candidate from "nominated" to
  "under evaluation."
- Rating Submission: assigned Faculty rate each indicator (1–5, with optional remarks) directly
  on the candidate's page — no separate "my evaluations" page, since Faculty's existing
  Graduating Evaluation list is scoped to just the candidates they're assigned to.

## Features (Phase 4C)

- Department Recommendation: a Department Head recommends a candidate from their own
  department once the requirement checklist and competency evaluation are both complete.
- Dean Approval: the Dean approves or rejects a recommended candidate, college-wide.
- Every recommendation/decision records who acted, when, and any remarks, shown on the
  candidate's page alongside the rest of their graduation record.

## Features (Phase 4D)

- Graduation conferral: an Admin marks an approved candidate as officially graduated once
  commencement is confirmed, distinct from — and later than — the Dean's approval.
- Individual candidate PDF report: checklist, competency ratings, recommendation, and decision
  in one downloadable document.
- Batch graduation list PDF: the official list of approved/graduated candidates for a given
  academic year and semester, scoped to what the requesting role can already see.

## Features (Phase 5A)

- Faculty Profiles: academic rank, employment status, specialization, office location, date
  hired, and bio for every Faculty account, created on first view rather than requiring a setup
  step.
- Admin manages every field for any faculty member; Dean (college-wide) and Department Head
  (own department) can view but not edit; a Faculty member can edit a limited set of fields on
  their own profile only.

## Features (Phase 5B)

- Education: degrees earned (level, degree, field of study, institution, year completed).
- Credentials: professional licenses and certifications, with issued/expiry dates.
- Trainings: seminars, workshops, and conferences attended, with hours logged.
- Awards: recognitions and honors received.
- All four are Admin-managed only (no Faculty self-edit, unlike the core profile fields);
  Dean, Department Head, and the Faculty member themselves see them read-only.

## Features (Phase 5C)

- Faculty Documents: category-tagged uploads (diploma, transcript of records, professional
  license, appointment letter, certificate of employment, performance evaluation, other),
  downloaded via a streamed, never-publicly-served response.
- A faculty member may upload their own documents — unlike Phase 5B's Admin-only records —
  because an upload only ever enters as "pending"; it is not treated as verified until an Admin
  reviews it.
- Admin verify/reject workflow with reviewer attribution and optional remarks; Admin-only
  deletion removes both the database record and the stored file.
- Dean (college-wide) and Department Head (own department) can view and download but not
  upload, verify, or delete.

## Features (Phase 5D)

- Faculty Workload: teaching load computed on demand from existing class-section faculty
  assignments — no separate workload table to keep in sync.
- "My Classes": a Faculty member sees their own assigned sections, units, schedule, and total
  units for a selected semester (defaults to the current one).
- Workload dashboard: Admin (college-wide), Dean (college-wide), and Department Head (own
  department) see every faculty member's section count and total units, including faculty with
  no assigned sections, with a drill-down into any faculty member's individual class list.

Phase 5 (Faculty Management) is now fully complete.

## Features (Phase 6A)

- Announcements: college-wide or department-scoped bulletins. Admin can post to any audience;
  a Department Head can post to their own department only (enforced server-side, not just hidden
  in the UI); Dean and Faculty see them read-only.
- Events: a shared calendar with an optional end time and location, scoped the same way as
  announcements. Defaults to showing upcoming events only, with a toggle to include past ones.
- Editing/deleting a department-scoped item is limited to that department's Department Head (or
  any Admin); a college-wide item can only be managed by an Admin.

## Features (Phase 6B)

- Meetings: college-wide or department-scoped scheduling, with attendee invitations and
  attendance tracking, and follow-up action items assigned to attendees.
- Attendee invitations and attendance marking are organizer-only (an Admin, or the meeting's own
  Department Head).
- Action items track a description, assignee, due date, status, and completion attribution. Only
  the meeting's organizer can create action items, but an assignee can update or complete the
  ones assigned to them even without any other management rights on the meeting.

## Features (Phase 6C)

- Tasks: general-purpose assignable to-dos, open to every role — no special permission needed.
  Anyone can create a task for themselves or assign it to someone else.
- The creator (or an Admin) can fully edit a task; the assignee can update or complete its
  status inline, but cannot change its title, reassign it, or delete it.
- Tasks are personal, not department-scoped: each user sees only the tasks they created or were
  assigned (Admin sees all, for oversight).

## Features (Phase 6D)

- Internal Requests: anyone can submit a request (leave, resource, equipment, or any other free-
  text type) for department or college approval.
- Approval is handled by an Admin (any department) or the requester's own Department Head (their
  department only) — never by the requester themselves, even if they'd otherwise have review
  authority.
- Every status change (submission, approval, rejection, cancellation) is automatically recorded
  in an audit trail, including the reviewer's remarks.
- The requester can withdraw (cancel) their own request while it's still pending. Faculty see
  only their own requests; a Department Head sees their whole department's, for review.

## Features (Phase 6E)

- Document Repository: a general college document store (policies, forms, minutes), distinct
  from the student- and faculty-specific document uploads elsewhere in the app.
- Admin-managed categories organize the repository; documents themselves can be college-wide or
  scoped to a single department, following the same visibility rules as Announcements/Events/
  Meetings.
- Full version history: every re-upload adds a new version rather than replacing the file,
  with the latest version always surfaced automatically. A document always keeps at least one
  version — the last one can only be removed by deleting the whole document.

## Features (Phase 6F)

- Notifications: a bell icon in the header (unread count + recent list) on every page, plus a
  full Notifications page, covering four events — a new announcement, a meeting invitation, a
  task assignment, and an internal request being approved or rejected.
- Clicking a notification marks it read and takes you to the relevant page; "Mark all read" is
  available from both the bell dropdown and the full page.
- Every notification respects the visibility rules already established for its underlying
  resource — for example, only users within an announcement's audience are ever notified about
  it.

Phase 6 (College Operations) is now fully complete.

## Features (Phase 7A)

- Research Projects: Faculty create and manage their own department's research projects
  (title, description, status, timeline, funding source); Administrators can create and manage
  a project for any department.
- Member roster with exactly one lead per project — the creator is automatically the first
  lead member. Only the lead (or an Administrator) can edit the project, add/remove members, or
  add/remove outputs.
- Outputs (publications, presentations, patents, technical reports, ...) recorded per project,
  each with an optional reference URL.
- Department Head sees every project in their own department but cannot create, edit, or manage
  one — a view-only role for this module, per the spec.

## Features (Phase 7B)

- Extension Projects: the same self-service model as Research — Faculty create and manage
  their own department's extension projects; Administrators can create and manage a project for
  any department; Department Head is view-only.
- Member roster with exactly one lead per project, same as Research.
- Activities conducted (trainings, outreach events, field demonstrations, ...), each with an
  optional date and location.
- Beneficiaries reached (individuals, cooperatives, LGUs, communities, ...) with an optional
  headcount.

## Features (Phase 7C)

- Facilities: laboratories, farms, greenhouses, field locations, classrooms, and other college
  spaces, each either shared/college-wide or scoped to a department. Admin manages any facility;
  Department Head manages only their own department's.
- Class section schedules now reference a real Facility instead of a free-text room name —
  existing historical room values were automatically migrated into facility records.

## Features (Phase 7D)

- Equipment inventory: microscopes, tractors, laptops, and other equipment, each optionally
  assigned to a facility and either shared/college-wide or department-owned.
- Borrow/return workflow: recording a borrowing marks the item Borrowed and shows who has it;
  recording a return marks it Available again. Equipment already borrowed can't be borrowed
  again until it's returned.
- Maintenance workflow: reporting an issue marks the item Under Maintenance; marking it complete
  restores Available status.
- Accountability report: a live, always-current list of every equipment item currently checked
  out and who is responsible for it — not a separately maintained list, just a query over the
  borrowing/return history.

Phase 7 (Agriculture Support Modules) is now fully complete.

## Features (Phase 8A)

- Role-based dashboards replacing the old placeholder counts: Administrator and Dean see
  college-wide KPIs and charts (students by status, graduation pipeline, at-risk students by
  department, equipment by status); Department Head sees the same shape scoped to their own
  department; Faculty sees a personal view (their class sections, advisees, open tasks, and own
  research/extension work).
- Every number is computed live on page load — no separate analytics tables, nothing to fall out
  of sync with the records that produce it.

## Features (Phase 8B)

- Five printable/exportable canned reports covering "Generate authorized college reports":
  Enrollment Summary, Academic Performance (Grade Distribution), At-Risk & Progress Summary,
  Faculty Workload Summary, and Graduation Pipeline Summary — each downloadable as PDF or Excel.
- Admin and Dean see college-wide data by default with an optional department filter; Department
  Head is automatically scoped to their own department with no department picker; Faculty has no
  access at all, per the spec's explicit permission row.
- The on-screen preview and the downloaded PDF/Excel file are generated by the exact same query,
  every time — no cached or precomputed report data to drift out of sync.

## Features (Phase 8C)

- Admin-only database backup and restore: trigger a full `mysqldump` backup, list existing
  backups with size/created date, download any backup, or restore the entire database from one
  (behind a confirm dialog that names the exact file and warns the action is irreversible).
- No other role has any access at all — the strictest permission tier in the system, matching the
  spec's row exactly.
- Every backup/restore attempt (success or failure) is recorded in the existing Audit Logs viewer.

## Features (Phase 8D)

Hardening and finalization — no new user-facing module, but real fixes found by three
independent codebase-wide audits (performance, authorization, mass-assignment) plus an
accessibility pass. See `PROJECT_PLAN.md`'s Phase 8D section and `ASSUMPTIONS.md` for the full
findings; the short version:

- Fixed an authorization gap that let any authenticated user read another department's import
  batches (including raw imported PII), and a mass-assignment gap that let a Department Head
  plant or move a student record into another department via a crafted request.
- Fixed two N+1 query patterns hit on every Faculty Workload dashboard/report load.
- Fixed accessibility gaps found on the Dashboard, the shared pagination component (used by every
  list page), and a systemic missing-label bug on 3 of 18 form pages.
- Full regression: 341 Pest tests passing, Pint/`tsc`/`npm run build` all clean.
- Wrote the four documentation files deferred since Phase 1: `DEPLOYMENT.md`,
  `BACKUP_RESTORE.md`, `USER_GUIDE.md`, `API_DOCUMENTATION.md`.

This closes Phase 8 and the project's original 8-phase build plan.

## Technology Stack

| Layer | Choice |
|---|---|
| Backend | Laravel 12, PHP 8.2+ |
| Database | MySQL/MariaDB |
| Auth | Laravel Breeze (React/Inertia/TypeScript stack) |
| Authorization | Laravel Policies/Gates + `spatie/laravel-permission` |
| Audit logging | `spatie/laravel-activitylog` + custom `login_logs` |
| Frontend | React 18 + TypeScript, Inertia.js, Tailwind CSS |
| Testing | Pest |
| Code style | Laravel Pint |

## Installation

See `INSTALLATION.md` for full setup steps. Quick start (assumes PHP 8.2+, Composer, Node.js,
and a running MySQL/MariaDB server are already installed):

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# edit .env: set DB_DATABASE/DB_USERNAME/DB_PASSWORD for your MySQL server
php artisan migrate --seed
npm run build
php artisan serve
```

## Seed (Development) Accounts

**Development only — never use these credentials in production.** Every seeded account
shares the password `Password123!` and does *not* require a forced password change (real,
admin-created accounts do require one — see `ASSUMPTIONS.md`).

| Role | Email | Username |
|---|---|---|
| College Administrator | admin@ca-apoms.test | admin |
| College Dean | dean@ca-apoms.test | dean |
| Department Head (per department) | head.{deptcode}@ca-apoms.test | head.{deptcode} |
| Faculty Member (2 per department) | faculty{1,2}.{deptcode}@ca-apoms.test | faculty{1,2}.{deptcode} |

Department codes: `cropsci`, `ansci`, `agecon`, `ageng`.

## Running the Application

```bash
php artisan serve       # backend, http://localhost:8000
npm run dev              # Vite dev server with HMR (separate terminal, for local dev)
```

For production-style asset serving, run `npm run build` once instead of `npm run dev`.

## Running Queues

No queued jobs exist yet. Phase 2's Excel imports run synchronously (validate-then-persist
per row, on the request) rather than being queued — see `ASSUMPTIONS.md` for why a staged
preview/queue step wasn't needed. Queues will be introduced when a later phase's workload
(e.g. bulk report generation) actually needs them:

```bash
php artisan queue:work
```

## Running Scheduled Tasks

No scheduled commands exist yet. Once added (credential-expiry reminders, etc.), run the
scheduler with:

```bash
php artisan schedule:work   # local dev
# or a real cron entry calling `php artisan schedule:run` every minute in production
```

## Running Tests

```bash
php artisan test
# or
./vendor/bin/pest
```

Tests run against an in-memory SQLite database (see `phpunit.xml`), never your real MySQL
database.

## Code Style

```bash
./vendor/bin/pint
```

## Building Production Assets

```bash
npm run build
```

## Documentation Index

- `PROJECT_PLAN.md` — phases, folder structure, current status.
- `DATABASE_DESIGN.md` — schema conventions, Phase 1–8D ERDs, full planned table inventory.
- `ROLE_PERMISSIONS.md` — permission matrix by role and module.
- `ASSUMPTIONS.md` — practical decisions made where the spec didn't specify a value.
- `INSTALLATION.md` — detailed local development setup.
- `DEPLOYMENT.md` — production/staging deployment guide.
- `BACKUP_RESTORE.md` — operational guide for the Backup and Restore module.
- `USER_GUIDE.md` — role-by-role guide to using the application.
- `API_DOCUMENTATION.md` — the Inertia route surface, organized by module.
- `.env.example` — all required environment variables.

# API_DOCUMENTATION.md

Route surface reference for CA-APOMS.

## This is not a public REST API

CA-APOMS is a server-rendered Inertia.js monolith, not a JSON API meant for external clients.
Every route in this document is part of the same authenticated web application — there are no
API tokens, no versioned `/api/v1/...` namespace, and no OAuth/API-key authentication layer. If
you're looking to integrate an external system with CA-APOMS, there currently isn't a supported
integration surface beyond the Excel/CSV import and PDF/Excel export flows described below;
building a real API layer would be a new phase of work, not something this document describes as
already existing.

## Request/response model

- **Authentication**: Laravel session cookies (`SESSION_DRIVER=database`), established via the
  standard Breeze login flow (`POST /login`). Every route below requires an authenticated session
  (`Route::middleware('auth')` wraps the entire application in `routes/web.php`) — hitting any of
  them unauthenticated redirects to `/login`.
- **CSRF**: every state-changing request (`POST`/`PUT`/`PATCH`/`DELETE`) requires a valid CSRF
  token. Inertia's client (`@inertiajs/react`) and Laravel's `VerifyCsrfToken` middleware handle
  this automatically for anything driven through the UI (via the `XSRF-TOKEN` cookie); a script
  hitting these routes directly needs to replicate that handshake.
- **`GET` routes** render a full Inertia page on a normal browser navigation, or return just the
  changed page's JSON props on an `X-Inertia: true` XHR (what the SPA sends for in-app
  navigation). There is no separate JSON-only endpoint for the same data — the props *are* the
  data.
- **`POST`/`PUT`/`PATCH`/`DELETE` routes** validate via a `FormRequest`, perform the write, and
  redirect back (Inertia's client follows the redirect automatically and re-renders with fresh
  props plus a flash message). None of them return a JSON body describing the created/updated
  resource — the redirect + re-render *is* the response.
- **Validation errors** come back as a 422 with Inertia's standard `errors` prop shape, which the
  client-side `useForm()` hook already knows how to read; there's no separate error-response
  format to document.
- **File downloads** (PDF/Excel exports, document/backup downloads) are the one category of route
  that returns a real binary response with `Content-Disposition: attachment` instead of an Inertia
  page — see the dedicated section below.

## Authorization

Two different mechanisms gate access, and which one applies depends on the route — this reflects
the actual audited state of the codebase (Phase 8D), not just a stated intention:

1. **Route-level `permission:` middleware** — used only for `users`, `audit-logs`, `reports`, and
   `backups`. These four groups are wrapped in `Route::middleware('permission:<name>')` in
   `routes/web.php`, so an unauthorized request never reaches the controller at all.
2. **Controller-level / FormRequest-level checks** — every other route relies on
   `$this->authorize()` inside the controller action or a `FormRequest::authorize()` method that
   calls a Policy, both ultimately backed by the same `spatie/laravel-permission` permissions and
   Policy classes. This is the majority pattern in the app.

Either way, the actual permission required for a given action is documented per-capability in
`ROLE_PERMISSIONS.md` — that file, not this one, is the source of truth for "who can do X."
Department-scoping (a Department Head only ever seeing their own department's rows) is enforced
inside the query/Policy itself, not by the route.

## Route reference by module

Base path, primary controller, and how each group is authorized. Every group additionally
supports the standard resource actions (`index`/`create`/`store`/`edit`/`update`/`destroy`) unless
noted otherwise; module-specific sub-actions are called out explicitly. Run
`php artisan route:list` on a checked-out copy of the app for the exact, current, line-by-line
list — this table is a map of it, not a substitute for it.

| Module | Base path | Controller | Notable sub-actions | Authorization |
|---|---|---|---|---|
| Dashboard | `/dashboard` | `DashboardController` | — | any authenticated user (role-conditional content) |
| Profile | `/profile` | `ProfileController` | — | the authenticated user, own profile only |
| Departments | `/departments` | `Academic\DepartmentController` | — | controller/Policy (`departments.manage`) |
| Programs | `/programs` | `Academic\ProgramController` | — | controller/Policy (`programs.manage`) |
| Academic Years | `/academic-years` | `Academic\AcademicYearController` | — | controller/Policy (`academic-terms.manage`) |
| Semesters | `/semesters` | `Academic\SemesterController` | — | controller/Policy (`academic-terms.manage`) |
| Courses | `/courses` | `Academic\CourseController` | — | controller/Policy (`courses.manage`) |
| Curricula | `/curricula` | `Academic\CurriculumController` | `/curricula/{c}/courses` (`CurriculumCourseController`) | controller/Policy (`curricula.manage`) |
| Students | `/students` | `Student\StudentController` | `/students/{s}/documents` (`StudentDocumentController`) | controller/Policy (`students.manage`/`.view`), department-scoped |
| Class Sections | `/class-sections` | `Enrollment\ClassSectionController` | `/roster`, `/schedules` (`ClassScheduleController`) | controller/Policy (`enrollment.manage`/`.view`) |
| Enrollments | `/enrollments` | `Enrollment\StudentEnrollmentController` | `/enrollments/{e}/courses` (`EnrollmentCourseController`) | controller/Policy (`enrollment.manage`) |
| Grades | `/grades`, `/class-sections/{c}/grades` | `Grading\GradeController` | `/submit`, `/approve`, `/return`, `/finalize`, `/{g}/correct` | controller/Policy (`grades.encode`/`.review`/`.view`) |
| Data Import | `/imports` | `Import\ImportController` | `/imports/{type}/template`, `/imports/batches/{b}`, `/imports/batches/{b}/errors` | controller-level, per-type permission (`students.import`, `courses.manage`, `curricula.manage`, `enrollment.manage`, `grades.import`) — fixed in Phase 8D to also gate `show`/`errors`, not just `store` |
| Academic Progress | `/academic-progress` | `Progress\AtRiskController` | `/students/{s}/alerts/{a}/acknowledge` | controller/Policy (`progress.view`), scoped |
| Advising | `/advising` | `Advising\AdvisingController` | `/students/{s}/advising-records`, `/students/{s}/followups` | controller/Policy (`advising.manage`/`.view`) |
| Graduation Requirements | `/graduation-requirement-templates` | `Graduation\GraduationRequirementTemplateController` | — | controller/Policy (`graduation.manage`) |
| Competency Framework | `/competency-categories` | `Graduation\CompetencyCategoryController` | `/indicators` (`CompetencyIndicatorController`) | controller/Policy (`graduation.manage`) |
| Graduating Evaluation | `/graduation-candidates` | `Graduation\GraduationCandidateController` | `/requirements/{r}`, `/evaluators`, `/ratings/{i}`, `/recommendation`, `/decision`, `/confer`, `/report`, `/report/batch` | controller/Policy per action — recommend (Dept. Head), decide (Dean only), evaluate (assigned Faculty) |
| Faculty Profiles | `/faculty-profiles` | `Faculty\FacultyProfileController` | `/education`, `/credentials`, `/trainings`, `/awards`, `/documents` (dedicated sub-controllers each) | controller/Policy (`faculty-profiles.manage`/`.view`), own-profile for Faculty |
| Faculty Workload | `/faculty-workload` | `Faculty\FacultyWorkloadController` | `/faculty-workload/{user}` | controller/Policy, self/department/college scoped |
| Announcements | `/announcements` | `Operations\AnnouncementController` | — | controller/Policy (`operations.manage`) |
| Events | `/events` | `Operations\EventController` | — | controller/Policy (`operations.manage`) |
| Facilities | `/facilities` | `Operations\FacilityController` | — | controller/Policy (`operations.manage`) |
| Equipment | `/equipment` | `Operations\EquipmentController` | `/accountability`, `/borrowings`, `/borrowings/{b}/return`, `/maintenance`, `/maintenance/{m}/complete` | controller/Policy (`operations.manage`/`.view`) |
| Meetings | `/meetings` | `Operations\MeetingController` | `/attendees`, `/action-items` | controller/Policy (`operations.manage`) |
| Tasks | `/tasks` | `Operations\TaskController` | — | controller/Policy — creator/assignee scoped, Admin sees all |
| Internal Requests | `/internal-requests` | `Operations\InternalRequestController` | `/review`, `/cancel` | controller/Policy — review scoped to Department Head's own department |
| Document Categories | `/document-categories` | `Operations\DocumentCategoryController` | index/store/destroy only | controller/Policy (`operations.manage`) |
| Documents | `/documents` | `Operations\DocumentController` | `/versions`, `/versions/{v}/download` | controller/Policy (`operations.manage`/`.view`) |
| Notifications | `/notifications` | `NotificationController` | `/{n}/read`, `/read-all` | the authenticated user, own notifications only |
| Research Projects | `/research-projects` | `Research\ResearchProjectController` | `/members`, `/outputs` | controller/Policy (`research-extension.manage`/`.view`), leadership-scoped |
| Extension Projects | `/extension-projects` | `Extension\ExtensionProjectController` | `/members`, `/activities`, `/beneficiaries` | controller/Policy (`research-extension.manage`/`.view`), leadership-scoped |
| User Management | `/users` | `Admin\UserController` | `/{u}/reactivate` | **route-level** `permission:users.manage` (Administrator only) |
| Audit Logs | `/audit-logs` | `Admin\AuditLogController` | — | **route-level** `permission:audit-logs.view` |
| Reports | `/reports` | `ReportController` | `/{type}`, `/{type}/pdf`, `/{type}/excel` | **route-level** `permission:reports.view` |
| Backup and Restore | `/backups` | `Admin\BackupController` | `/{filename}/download`, `/{filename}/restore` | **route-level** `permission:backups.manage` (Administrator only) |

## File-download endpoints

These are the routes most likely to be useful outside the normal click-through UI (e.g. scripted
against with a valid session cookie for a batch download), since they return an actual file
rather than an Inertia page:

| Route | Returns |
|---|---|
| `GET /reports/{type}/pdf` | A PDF of the given report type (`enrollment`, `grades`, `at-risk`, `faculty-workload`, `graduation-pipeline`), respecting the requesting user's department scope. Accepts the same `semester_id`/`department_id` query filters as the on-screen preview. |
| `GET /reports/{type}/excel` | The same report as an `.xlsx` file. |
| `GET /graduation-candidates/{c}/report` | A single candidate's graduation evaluation report (PDF). |
| `GET /graduation-candidates/report/batch` | A ceremony-list PDF of approved/graduated candidates for a term. |
| `GET /students/{s}/documents/{d}/download` | A specific uploaded student document. |
| `GET /faculty-profiles/{u}/documents/{d}/download` | A specific uploaded faculty document. |
| `GET /documents/{d}/versions/{v}/download` | A specific version of a repository document. |
| `GET /imports/{type}/template` | An empty `.xlsx` import template (headers + one example row) for the given import type. |
| `GET /imports/batches/{b}/errors` | A `.csv` of the row-level errors for a given import batch. |
| `GET /backups/{filename}/download` | A raw `.sql` database backup file (Administrator only). `{filename}` is constrained by route regex to the exact `ca-apoms_YYYY-MM-DD_HHmmss.sql` pattern — any other value 404s before reaching the controller. |

## Extending this surface

If a future phase adds a genuine external-facing API (mobile app, a registrar-system integration,
etc.), that almost certainly means a new, separate route group under `routes/api.php` with
Sanctum token authentication and versioned JSON responses — a different shape from everything
described above, not an extension of it. Bolting token auth onto the existing Inertia routes
would conflate two different concerns (a browser session driving a UI vs. a script consuming
structured data) that are cleanly separate today.

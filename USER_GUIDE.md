# USER_GUIDE.md

A practical guide to using CA-APOMS, organized by role. For the exhaustive technical permission
matrix (every capability × every role), see `ROLE_PERMISSIONS.md`. For a list of every module and
when it shipped, see `PROJECT_PLAN.md`.

## Getting started

1. Go to the application URL and sign in with the email and password your Administrator gave
   you. New accounts created by an Administrator get a temporary password and are required to
   set a new one on first login.
2. The sidebar on the left lists every module you have access to — items you can't use for your
   role simply don't appear, rather than appearing disabled. If something you expect to see is
   missing, it's a permissions question for your Administrator, not a bug.
3. Your name, role, and (if applicable) department appear in the top-right corner. Click it to
   reach your profile settings or log out.
4. A bell icon in the header shows system notifications relevant to you (e.g. a task assigned to
   you, a request awaiting your review).

## Roles at a glance

CA-APOMS has four roles. Every department also has exactly one Department Head and a small
number of Faculty members; Administrator and Dean are college-wide roles with exactly one
account each in a typical deployment.

| Role | Scope | What they're for |
|---|---|---|
| **Administrator** | College-wide, full access | IT/registrar staff who run the system day-to-day: accounts, department/program setup, imports, backups. |
| **Dean** | College-wide, mostly view + approvals | Academic leadership: college-wide visibility into progress/workload/reports, and the sole approver of graduation recommendations. |
| **Department Head** | Own department only | Runs one department: students, enrollment, advising, faculty assignments, departmental operations — all scoped to their own department, enforced server-side. |
| **Faculty** | Own classes/advisees/profile | Teaching staff: encode grades for their own sections, advise their own advisees, maintain their own profile and research/extension work. |

---

## Administrator

You have full access to every module. Day-to-day, the modules you'll use most:

- **User Management** (`Settings → Users` / sidebar "User Management"): create accounts, assign
  a role and (for Department Head/Faculty) a department, deactivate or reactivate accounts. New
  accounts get a system-generated temporary password and must change it on first login.
- **Departments / Programs / Academic Years** (`Settings`): the foundational setup every other
  module depends on. Create departments and their programs before anything else; create academic
  years and semesters before enrollment can happen for a given term.
- **Data Import** (sidebar "Data Import"): bulk-load students, courses, curriculum-course links,
  enrollment, or grades from an Excel/CSV file instead of encoding one at a time. Download the
  template for the import type you need first — the column headers must match exactly. After
  importing, review the batch's error list (if any rows failed) before assuming the import fully
  succeeded; a partial import still reports success for the rows that worked.
- **Reports** (sidebar "Reports"): five canned reports (Enrollment Summary, Academic Performance,
  At-Risk & Progress Summary, Faculty Workload Summary, Graduation Pipeline Summary), each
  previewable on screen and downloadable as PDF or Excel. As Administrator you see college-wide
  data by default, with an optional department filter.
- **Backup and Restore** (sidebar "Backup and Restore", Administrator-only): trigger a database
  backup before any risky operation (a large import, a migration, anything you're not 100% sure
  about), and restore from one if something goes wrong. See `BACKUP_RESTORE.md` for the full
  guide — restoring is destructive and immediate, so read that before you need it in a hurry.
- **Audit Logs** (sidebar "Audit Logs"): a full history of who did what and when, across every
  module. Useful for investigating "who changed this" questions.

You are also the only role that can act as any department for setup purposes — e.g. registering a
student directly into any department, or creating a course under any department — since
Department Head actions are always scoped to their own department by the server, regardless of
what the form shows.

## Dean

Your access is mostly read-only and college-wide, with one specific approval authority.

- **Dashboard**: college-wide KPIs and charts — student status breakdown, graduation pipeline,
  at-risk students by department, equipment status.
- **Academic Progress**: college-wide visibility into every student's progress and deficiencies,
  not scoped to one department (unlike a Department Head, who only sees their own).
- **Graduating Evaluation → Approve/reject recommendations**: this is your one exclusive action —
  after a Department Head recommends a student for graduation and Competency evaluators have
  submitted their ratings, the recommendation lands on your desk for the final decision. No other
  role can approve or reject a graduation recommendation, including Administrator.
- **Faculty Workload**: college-wide workload visibility across every department, useful for
  comparing teaching loads.
- **Reports**: same five canned reports as Administrator, with the same college-wide default and
  optional department filter.
- **Audit Logs**: a curated, summary view — account/role-management entries are excluded from
  what you see (that stays Administrator-only), by design, per the specification's constraint
  that the Dean role never gets unrestricted access to raw technical/system-administration data.

You do **not** have access to User Management, Backup and Restore, or Data Import — those stay
Administrator-only regardless of your college-wide standing elsewhere in the system.

## Department Head

Everything you do is automatically scoped to your own department by the server — there's no
"switch department" control because there's nothing to switch to. If you try to reach another
department's data directly (e.g. by guessing a URL), you'll get a 403, not another department's
records.

- **Students**: register and update students in your department, including changing their status
  (active, on leave, dropped, etc.) with a required reason. You can view but not directly encode
  grades — grade review, not grade entry, is your role in the grading workflow.
- **Academic Progress / Advising**: see every student in your department's progress and
  deficiencies, record advising sessions and interventions for students in your department (not
  only your own advisees — that's the Faculty-level scope).
- **Enrollment / Class Sections**: manage enrollment records and assign faculty to class sections
  within your department.
- **Grades → Review**: review grades submitted by faculty in your department before they're
  finalized (you review; Faculty encode).
- **Graduating Evaluation → Recommend**: nominate eligible students in your department for
  graduation. Your recommendation goes to the Dean for the final approve/reject decision — you
  don't make that call yourself.
- **Faculty Profiles / Workload**: view faculty profiles and review workload for faculty in your
  department.
- **College Operations** (Announcements, Events, Meetings, Internal Requests): manage activities
  scoped to your department; college-wide announcements stay Administrator-only.
- **Research / Extension**: view records from your department; you don't need `.manage` rights to
  see what your department's faculty are working on.
- **Reports**: the same five canned reports, automatically scoped to your department with no
  department picker (there's nothing else for you to pick).

## Faculty

Your access centers on your own classes, your own advisees, and your own profile.

- **Grades**: encode and import grades for the class sections assigned to you. You cannot review
  or finalize your own submissions — that's the Department Head's role in the workflow — and you
  cannot see or touch another faculty member's sections.
- **Advising**: record advising sessions and interventions for students where you are the
  assigned adviser. You won't see advising records for students who aren't your advisees.
- **Academic Progress**: view progress and deficiencies for your own advisees only.
- **Graduating Evaluation → Competency Evaluation**: if you're assigned as an evaluator for a
  graduation candidate's competency rating, that assignment shows up here — this is the one piece
  of the graduation workflow Faculty participates in.
- **Faculty Profile**: maintain your own profile — education history, credentials, trainings,
  awards, and supporting documents. You can view (not edit) other faculty members' profiles in
  your department.
- **Faculty Workload**: view your own teaching load and schedule — sections, units, and weekly
  schedule for the selected semester.
- **Research / Extension**: submit and manage your own research/extension projects; you're
  automatically added as a member when you create one.
- **College Operations**: view announcements/events/meetings for your department; manage tasks
  assigned to or created by you.

You do not have access to Reports, Backup and Restore, User Management, or any student-management
screen beyond viewing.

---

## Common workflows

**Setting up a new academic term** (Administrator): Academic Years → create the year → create its
semesters → confirm the correct semester is marked "current" (this drives every default filter
across Dashboards, Reports, and enrollment screens).

**Onboarding a new student** (Administrator or Department Head, own department): Students →
Register Student → fill in personal info, department/program/curriculum/year level, and
guardian/emergency contact info → save. Alternatively, for bulk onboarding, use Data Import →
Students with a prepared spreadsheet.

**A semester's grading cycle** (Faculty encodes → Department Head reviews → grades finalize):
Faculty encode grades per class section under Grades; once submitted, the Department Head reviews
them under the same module; once approved, grades finalize and become visible on the student's
academic record and feed into progress computation (deficiencies, GWA, at-risk alerts) — the
whole pipeline is computed live, not on a schedule, so there's no "wait for overnight processing"
step anywhere in this system.

**Evaluating a graduating student** (multi-role, in order): Department Head nominates the student
under Graduating Evaluation once the system identifies them as eligible (100% curriculum
completion, no unresolved deficiencies) → assigned Faculty evaluators submit competency ratings →
Department Head submits a department recommendation → Dean makes the final approve/reject
decision. A rejected recommendation can be revised and resubmitted.

**Generating a report for a meeting** (Administrator, Dean, or Department Head): Reports → pick a
report type → adjust the semester/department filter if needed → either read it on screen or click
Download PDF/Excel. What you see on screen and what downloads are always identical, generated by
the same query at the same moment.

**Something went wrong and you need to undo it** (Administrator): if it's a single record, check
whether that module has its own "restore"/"reactivate" action first (several modules soft-delete
rather than permanently erase, and can be restored without a full database rollback). If it's
broader than one record, see `BACKUP_RESTORE.md` — restoring from a recent backup is the
system-wide undo button, but it rolls back *everything* since that backup, not just the one thing
that went wrong, so reach for it deliberately, not reflexively.

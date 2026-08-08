# BACKUP_RESTORE.md

Operational guide for the Backup and Restore module (Phase 8C) — how to back up the database,
what a backup does and doesn't cover, and how to restore from one safely.

## Who can do this

Backup and Restore is **Administrator-only**. No other role — including Dean — has any access to
`/backups`; a Dean or Department Head hitting that URL directly gets a `403 Forbidden`, and the
"Backup and Restore" link doesn't appear in their sidebar at all. This is the strictest permission
in the entire system, matching the original specification's permission row exactly.

## What a backup contains

Each backup is a complete `mysqldump` snapshot of the `ca_apoms` database at the moment you click
"Create Backup" — every table's schema and data, plus stored routines, triggers, and events
(`mysqldump --single-transaction --routines --events`). `--single-transaction` means the dump is
transactionally consistent even if other users are actively using the system while it runs; you
do not need to schedule backups for a quiet period or put the app in maintenance mode first.

**What a backup does *not* include**: uploaded files. Student documents, faculty documents, and
any other files stored under `storage/app/private/` (outside the `backups/` subfolder itself) are
plain files on disk, not database rows, and a database backup has no way to capture them. If your
deployment needs file-level disaster recovery too, back up `storage/app/private/` separately
through your normal server/filesystem backup process — that is outside what this module does.

## Creating a backup

1. Sign in as an Administrator and open **Backup and Restore** from the sidebar (or navigate to
   `/backups`).
2. Click **Create Backup**. This runs synchronously — the page will show "Creating Backup…"
   while `mysqldump` runs, typically a few seconds at department scale.
3. On success, the new backup appears at the top of the list with its filename, size, and
   creation time. On failure, a red toast explains why (see Troubleshooting below) and no
   partial file is left behind.

Every backup attempt — success or failure — is recorded in the Audit Logs page
(`/audit-logs`, filterable by log name `backups`), so there's always a record of who triggered a
backup and when, even if it failed.

## Downloading a backup

Click **Download** next to any backup in the list to save the raw `.sql` file locally. This is
useful for keeping an off-server copy — the application itself only stores backups on the same
disk as the rest of the app (`storage/app/private/backups/`), so if that server is lost entirely,
so are any backups still sitting on it. Periodically download and archive backups somewhere
independent of the application server (network storage, an offsite copy, etc.) as part of your
institution's normal backup discipline — this module makes creating the snapshot easy, but true
disaster recovery still depends on getting a copy off the machine.

## Restoring from a backup

**This is destructive and immediate.** Restoring replaces every row currently in the database
with the contents of the selected backup — any data recorded after that backup was taken (new
students, updated grades, new users, everything) is permanently lost the moment the restore
completes. There is no undo, and no confirmation beyond the one dialog.

1. Click **Restore** next to the backup you want to roll back to.
2. A confirmation dialog names the exact file and repeats the warning above. Read the filename
   carefully — it encodes the date and time the backup was taken
   (`ca-apoms_YYYY-MM-DD_HHmmss.sql`) — before confirming you have the right one.
3. Click **Restore Database** to proceed, or **Cancel** to back out.

**Before restoring in a real environment**: if you're restoring because something recently went
wrong, consider creating a fresh backup *first* — even of the "broken" state — so you have a way
back if the restore turns out to be the wrong call, or if you need to recover something from the
period between the old backup and now that wasn't actually affected by whatever prompted the
restore.

A successful (or failed) restore is logged the same way a backup is — check `/audit-logs`
(log name `backups`) to confirm it completed and see exactly when.

## Retention

The application does not delete old backups automatically — every backup you create stays in
`storage/app/private/backups/` until someone removes it directly from the server's filesystem.
There is no retention policy or automatic cleanup built into this module. If backups accumulate
faster than you want (each one is a full snapshot — expect low hundreds of KB to a few MB per
backup at department scale, growing as the institution's data grows), periodically download the
ones you want to keep and delete the rest directly on the server, or script a retention job
outside the application (e.g. "keep the last 30 days" via a server cron job that prunes
`storage/app/private/backups/ca-apoms_*.sql` by date) — this is intentionally left to
server/institutional policy rather than baked into the app.

## Troubleshooting

- **"Backup failed: mysqldump failed: ..."** — the `mysqldump` binary either isn't installed,
  isn't on the web server process's `PATH`, or the web server's OS user lacks permission to
  execute it. Set `DB_MYSQLDUMP_PATH` in `.env` to the binary's full path (see `INSTALLATION.md`
  and `DEPLOYMENT.md`) and confirm the web server user can actually run it.
- **"Can't create TCP/IP socket" specifically when using `php artisan serve`** — this is a known,
  fixed issue (see `ASSUMPTIONS.md`, Phase 8C) with PHP's built-in development server's
  environment handling; it does not affect a real web server deployment, and is exactly one of
  the reasons `DEPLOYMENT.md` says never to run `php artisan serve` in production.
- **Restore failed** — same binary/`PATH`/permission checks as above, but for `DB_MYSQL_PATH`
  and the `mysql` client instead of `mysqldump`. The database is left in whatever state it was in
  before the failed restore attempt — a failed restore does not partially apply.
- **A backup or restore is taking a long time** — both operations have a 5-minute timeout
  (`Process::timeout(300)` in `App\Services\BackupService`). At real institutional data volumes
  this should be far more time than needed; if you're consistently approaching it, that's a sign
  the database has grown large enough to warrant a dedicated backup strategy outside this module
  (e.g. a scheduled `mysqldump` via server cron, run directly on the database host).

# DEPLOYMENT.md

Production/staging deployment guide for CA-APOMS. `INSTALLATION.md` covers local development
setup (XAMPP, `php artisan serve`, `npm run dev`) — this document covers running the application
as a real, continuously-available service for the College of Agriculture.

## Do not run `php artisan serve` in production

Laravel documents its built-in server as development-only, and this project has a concrete,
verified reason to take that seriously: during Phase 8C (Backup and Restore) hardening, we found
that `mysqldump`/`mysql` — shelled out to via `Illuminate\Support\Facades\Process` — reliably
**fails** when the request is served by `php artisan serve`, but succeeds identically via
`php artisan tinker` or a real web server SAPI. Root cause: PHP's built-in server runs under the
`cli-server` SAPI, and Symfony Process's environment derivation only fully inherits the parent
process's environment on the `cli`/`phpdbg`/`embed` SAPIs — `cli-server` instead intersects
`getenv()` with `$_SERVER`, which on at least one real machine silently dropped a variable
`mysqldump.exe`'s Winsock initialization needed. The application code works around this
specifically (see `ASSUMPTIONS.md`, Phase 8C), but it is one confirmed example of `cli-server`
SAPI behaving differently from a production SAPI in ways that are easy to miss. Deploy behind
**Apache + mod_php**, **Apache/Nginx + PHP-FPM**, or **IIS + PHP FastCGI** — never the built-in
server, and never a process manager that just runs `php artisan serve` in the background.

## Server requirements

- PHP 8.2+ with the extensions Laravel 12 requires: `pdo_mysql`, `mbstring`, `openssl`,
  `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd` or `imagick` (for
  `barryvdh/laravel-dompdf`).
- Composer 2.x (build-time only — not required on the production host if you deploy a
  pre-built `vendor/` directory).
- Node.js 18+ / npm (build-time only — the production host only needs the compiled
  `public/build/` assets, not Node itself).
- MySQL 8+ or MariaDB 10.4+, reachable from the application host.
- A real web server: Apache (with `mod_rewrite`) or Nginx + PHP-FPM.
- `mysqldump`/`mysql` client binaries installed and reachable (see Phase 8C notes below).

## Environment configuration

Copy `.env.example` to `.env` on the target host and set, at minimum:

```
APP_NAME="CA-APOMS"
APP_ENV=production
APP_KEY=                      # generate fresh — see below, never reuse a dev key
APP_DEBUG=false                # never true in production — leaks stack traces to visitors
APP_URL=https://your-domain.example

DB_CONNECTION=mysql
DB_HOST=<production-db-host>
DB_PORT=3306
DB_DATABASE=ca_apoms
DB_USERNAME=<dedicated-app-user>   # not root — see below
DB_PASSWORD=<strong-generated-password>

SESSION_DRIVER=database
SESSION_DOMAIN=your-domain.example
SESSION_SECURE_COOKIE=true     # requires HTTPS — see below

MAIL_MAILER=<a-real-driver>    # "log" (the local default) silently drops mail — fine for
                                # dev, wrong for production once any notification is wired up
```

Generate a fresh application key on the target host — never copy `APP_KEY` from a development
`.env`:

```bash
php artisan key:generate
```

**Database user**: create a dedicated MySQL user scoped to the `ca_apoms` database only, rather
than using `root` (the local-dev default documented in `INSTALLATION.md`). The application only
ever needs `SELECT`/`INSERT`/`UPDATE`/`DELETE`/`CREATE TEMPORARY TABLES`/`LOCK TABLES` on that one
database; it never needs server-wide privileges.

**`DB_MYSQLDUMP_PATH`/`DB_MYSQL_PATH`** (Phase 8C): if `mysqldump`/`mysql` aren't already on the
web server process's `PATH`, set these to their absolute path, exactly as documented in
`INSTALLATION.md`. Confirm the web server's OS user actually has execute permission on those
binaries — a permission gap here fails the same way as the `cli-server` bug above (a caught
`RuntimeException` surfaced as a flashed error), not a fatal crash, so check the Audit Logs page
(`log_name = "backups"`) if backups silently never appear.

## Deployment steps

1. **Pull the code** onto the target host (git clone/pull, or a build artifact from CI).
2. **Install PHP dependencies** without dev tooling:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
3. **Build frontend assets** (can be done in CI and shipped as part of the deploy artifact
   instead of building on the production host):
   ```bash
   npm ci
   npm run build
   ```
4. **Set `.env`** per the section above.
5. **Run migrations**:
   ```bash
   php artisan migrate --force
   ```
   `--force` is required because Laravel prompts for confirmation in `APP_ENV=production` by
   default. Never run `migrate:fresh` against a database holding real institutional data — it
   drops every table. Take a backup first regardless (see `BACKUP_RESTORE.md`).
6. **Do not run the database seeders in production.** They exist for local development and the
   Pest suite only (fake departments, fake students, a fixed set of demo accounts with published
   passwords). Create the first real Administrator account directly, or via a one-off `tinker`
   session, not `UserSeeder`.
7. **Link storage** (only relevant if you ever store files on the `public` disk; the app uses the
   `local`/private disk for documents and backups, so this is mostly a no-op today but is
   Laravel's standard step):
   ```bash
   php artisan storage:link
   ```
8. **Set file permissions** so the web server user can write to `storage/` and
   `bootstrap/cache/` (logs, sessions if file-based, compiled views, cached config, Phase 8C
   backups under `storage/app/private/backups/`):
   ```bash
   chmod -R 775 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache   # adjust user:group to your web server
   ```
9. **Cache configuration, routes, and views** for production performance:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
   If you change `.env` after this, you must re-run `config:cache` (or `config:clear`) — cached
   config takes priority over `.env` once cached.

## Web server configuration

The document root must point at `public/`, never the project root — everything outside `public/`
(including `.env`, `app/`, `storage/`) must not be web-accessible.

**Apache** (a working `public/.htaccess` already ships with the project for `mod_rewrite`):
```apache
<VirtualHost *:443>
    ServerName your-domain.example
    DocumentRoot /path/to/ca-apoms/public

    <Directory /path/to/ca-apoms/public>
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile      /path/to/cert.pem
    SSLCertificateKeyFile   /path/to/key.pem
</VirtualHost>
```

**Nginx + PHP-FPM**:
```nginx
server {
    listen 443 ssl;
    server_name your-domain.example;
    root /path/to/ca-apoms/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    ssl_certificate     /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
}
```

## HTTPS

Every role in this system handles PII (student records, contact info, grades) and session
cookies — always deploy behind HTTPS. Set `SESSION_SECURE_COOKIE=true` once HTTPS is in place so
the session cookie is never sent over plain HTTP.

## Background processes

The application currently has **no queued jobs** (`ShouldQueue` is unused throughout) and **no
scheduled tasks** (no `Schedule::` entries). `QUEUE_CONNECTION=database` in `.env.example` is
Laravel's stock default and is not exercised by anything in the app today — you do **not** need a
`queue:work` process or a cron entry running `schedule:run` for the application to function.
Revisit this note if a future phase introduces a queued job (e.g. a large async export) or a
scheduled task (e.g. an automated nightly backup beyond Phase 8C's manual, Admin-triggered one).

## Post-deploy verification checklist

- [ ] `https://your-domain.example/login` loads without a 500 error.
- [ ] Log in with the freshly created Administrator account (not a seeded demo account).
- [ ] `/dashboard` renders without errors for at least one account per role.
- [ ] A file download works (e.g. a Phase 8B report PDF/Excel export) — confirms
  `barryvdh/laravel-dompdf`/`maatwebsite/excel` have the PHP extensions they need.
- [ ] Phase 8C: `/backups` → "Create Backup" produces a real file and the Audit Logs page shows a
  `backups` log entry with no error. This is the single most likely thing to silently misconfigure
  on a new host (missing `mysqldump` on `PATH`, or a permissions gap) — verify it explicitly
  rather than assuming it works because everything else does.
- [ ] `storage/logs/laravel.log` has no unexpected errors after the above.

## Updating an existing deployment

```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Take a Phase 8C backup before running `migrate --force` on a host with real data — a migration
bug is exactly the scenario Backup and Restore exists for. See `BACKUP_RESTORE.md`.

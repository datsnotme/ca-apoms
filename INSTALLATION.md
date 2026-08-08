# INSTALLATION.md

Detailed local development setup for CA-APOMS.

## Prerequisites

- PHP 8.2 or newer, with the extensions Laravel requires (pdo_mysql, mbstring, openssl,
  tokenizer, xml, ctype, json, bcmath, fileinfo — all bundled with a standard XAMPP PHP
  install).
- Composer 2.x.
- Node.js 18+ and npm.
- A running MySQL-compatible server (MySQL 8+ or MariaDB 10.4+). Local development was built
  and tested against XAMPP's bundled MariaDB.

## 1. Get the code and install dependencies

```bash
composer install
npm install
```

## 2. Environment file

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and confirm the database block matches your server:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ca_apoms
DB_USERNAME=root
DB_PASSWORD=
```

If you're using XAMPP, its MariaDB root user has no password by default, matching the
example above. For a hardened install, create a dedicated MySQL user instead of using root.

The Backup and Restore module (Phase 8C) shells out to `mysqldump`/`mysql`. If those binaries
aren't on your system `PATH` — the usual case for a XAMPP install — uncomment and set
`DB_MYSQLDUMP_PATH`/`DB_MYSQL_PATH` in `.env` to their full path (e.g.
`C:/xampp/mysql/bin/mysqldump.exe`); use forward slashes even on Windows, since `.env` parsing
rejects backslash escapes.

## 3. Create the database

The application does not create its own database. Create it once, empty, before migrating:

```sql
CREATE DATABASE ca_apoms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Via the `mysql` CLI:

```bash
mysql -u root -e "CREATE DATABASE ca_apoms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

## 4. Run migrations and seed development data

```bash
php artisan migrate --seed
```

This creates every Phase 1 table and seeds:

- The four roles and their Phase 1 permissions.
- One college, four agriculture departments, five programs, two academic years, six
  semesters (see `database/seeders/CollegeDepartmentSeeder.php`).
- One admin, one dean, one department head per department, and two faculty members per
  department (see `database/seeders/UserSeeder.php` and the account table in `README.md`).

To start over from a clean database:

```bash
php artisan migrate:fresh --seed
```

**`migrate:fresh` drops every table.** Never run it against a database with real institutional
data.

## 5. Build frontend assets

For local development with hot module reloading:

```bash
npm run dev
```

Or build once for a `php artisan serve`-only workflow:

```bash
npm run build
```

## 6. Serve the application

```bash
php artisan serve
```

Visit `http://localhost:8000` (redirects to `/login`). Sign in with any account from the
seed table in `README.md`.

## Troubleshooting

- **"Class not found" errors after adding a package**: run `composer dump-autoload`.
- **"SQLSTATE[HY000] [2002] Connection refused"**: your MySQL/MariaDB server isn't running,
  or `DB_HOST`/`DB_PORT` in `.env` don't match it.
- **Changes to `resources/js` not appearing**: make sure `npm run dev` is running, or rebuild
  with `npm run build` if you're not using the dev server.
- **419 Page Expired on login**: usually a stale session cookie from a previous `APP_KEY`;
  clear cookies for the site or run `php artisan config:clear`.

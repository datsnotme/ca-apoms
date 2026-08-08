<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup Storage
    |--------------------------------------------------------------------------
    |
    | Database backups (Phase 8C) are stored as plain .sql files on this disk,
    | under this path. The disk must not be publicly accessible — a database
    | dump contains every row in the system.
    |
    */

    'disk' => 'local',
    'path' => 'backups',

    /*
    |--------------------------------------------------------------------------
    | MySQL Client Binaries
    |--------------------------------------------------------------------------
    |
    | Defaults assume `mysqldump`/`mysql` are on PATH. Override via .env if
    | they are not (e.g. a XAMPP install where they live under
    | C:\xampp\mysql\bin\mysqldump.exe).
    |
    */

    'mysqldump_binary' => env('DB_MYSQLDUMP_PATH', 'mysqldump'),
    'mysql_binary' => env('DB_MYSQL_PATH', 'mysql'),

];

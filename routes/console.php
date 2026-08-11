<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Requires a real OS-level cron entry running `php artisan schedule:run`
// every minute in production — see DEPLOYMENT.md. Does nothing on its own
// under `php artisan serve` in local dev.
Schedule::command('alerts:at-risk')->dailyAt('07:00');

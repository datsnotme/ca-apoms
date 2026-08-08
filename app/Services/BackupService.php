<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin-only "Perform backup and restore" capability (Phase 8C). Backups are
 * plain .sql files on disk — the filesystem is the source of truth, no
 * separate database table tracks them (same "compute on demand, persisted
 * only where a workflow needs it" discipline as Dashboards/Reports). Who
 * triggered a backup or restore, and when, is recorded via the existing
 * spatie/laravel-activitylog infrastructure under log_name "backups", so it
 * shows up in the Phase 1 Audit Logs viewer without a new table either.
 */
class BackupService
{
    /**
     * Only this exact shape is ever accepted back from the frontend as a
     * filename — anywhere it's used (route, download, restore) — closing off
     * path traversal since a filename is never taken as free-form input.
     */
    public const FILENAME_PATTERN = '/^ca-apoms_\d{4}-\d{2}-\d{2}_\d{6}\.sql$/';

    /**
     * Same shape as FILENAME_PATTERN, without PCRE delimiters/anchors, for
     * use in Route::where() (which wraps it in its own anchors).
     */
    public const FILENAME_PATTERN_ROUTE = 'ca-apoms_\d{4}-\d{2}-\d{2}_\d{6}\.sql';

    /**
     * @return Collection<int, array{filename: string, size: int, sizeHuman: string, createdAt: string}>
     */
    public function list(): Collection
    {
        $disk = Storage::disk(config('backup.disk'));
        $path = config('backup.path');

        return collect($disk->files($path))
            ->filter(fn (string $file) => preg_match(self::FILENAME_PATTERN, basename($file)) === 1)
            ->map(fn (string $file) => [
                'filename' => basename($file),
                'size' => $disk->size($file),
                'sizeHuman' => $this->humanSize($disk->size($file)),
                'createdAt' => date('Y-m-d H:i:s', $disk->lastModified($file)),
            ])
            ->sortByDesc('createdAt')
            ->values();
    }

    /**
     * @return array{filename: string, size: int}
     */
    public function create(User $user): array
    {
        $connection = config('database.connections.mysql');
        $filename = 'ca-apoms_'.now()->format('Y-m-d_His').'.sql';

        $command = array_values(array_filter([
            config('backup.mysqldump_binary'),
            '--host='.$connection['host'],
            '--port='.$connection['port'],
            '--user='.$connection['username'],
            '--single-transaction',
            '--routines',
            '--events',
            $connection['database'],
        ]));

        $result = Process::timeout(300)
            ->env($this->mysqlEnv($connection))
            ->run($command);

        if ($result->failed()) {
            activity('backups')->causedBy($user)->log("Backup failed: {$result->errorOutput()}");

            throw new RuntimeException('mysqldump failed: '.trim($result->errorOutput() ?: $result->output()));
        }

        $disk = Storage::disk(config('backup.disk'));
        $path = config('backup.path').'/'.$filename;
        $disk->put($path, $result->output());

        activity('backups')->causedBy($user)->log("Created backup {$filename}");

        return ['filename' => $filename, 'size' => $disk->size($path)];
    }

    public function restore(string $filename, User $user): void
    {
        $this->assertValidFilename($filename);

        $disk = Storage::disk(config('backup.disk'));
        $path = config('backup.path').'/'.$filename;

        if (! $disk->exists($path)) {
            throw new RuntimeException("Backup {$filename} does not exist.");
        }

        $connection = config('database.connections.mysql');

        $command = array_values(array_filter([
            config('backup.mysql_binary'),
            '--host='.$connection['host'],
            '--port='.$connection['port'],
            '--user='.$connection['username'],
            $connection['database'],
        ]));

        $result = Process::timeout(300)
            ->env($this->mysqlEnv($connection))
            ->input($disk->get($path))
            ->run($command);

        if ($result->failed()) {
            activity('backups')->causedBy($user)->log("Restore from {$filename} failed: {$result->errorOutput()}");

            throw new RuntimeException('mysql restore failed: '.trim($result->errorOutput() ?: $result->output()));
        }

        activity('backups')->causedBy($user)->log("Restored database from backup {$filename}");
    }

    public function download(string $filename): StreamedResponse
    {
        $this->assertValidFilename($filename);

        $disk = Storage::disk(config('backup.disk'));
        $path = config('backup.path').'/'.$filename;

        if (! $disk->exists($path)) {
            abort(404);
        }

        return $disk->download($path, $filename);
    }

    /**
     * @param  array<string, mixed>  $connection
     * @return array<string, string>
     */
    private function mysqlEnv(array $connection): array
    {
        // Symfony Process's default environment derivation only fully
        // inherits the parent process's env on the "cli"/"phpdbg"/"embed"
        // SAPIs. `php artisan serve` runs under "cli-server", which instead
        // takes the intersection of getenv() with $_SERVER — and on this
        // Windows host that silently drops variables mysqldump/mysql's own
        // Winsock init depends on, failing with "Can't create TCP/IP socket
        // (10106)". Passing the full getenv() explicitly here sidesteps that
        // filtering entirely, regardless of which SAPI ends up serving the
        // request.
        return array_filter(['MYSQL_PWD' => $connection['password'] ?: null]) + getenv();
    }

    private function assertValidFilename(string $filename): void
    {
        if (preg_match(self::FILENAME_PATTERN, $filename) !== 1) {
            abort(404);
        }
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return "{$bytes} B";
        }

        $value = $bytes / 1024;

        foreach (['KB', 'MB', 'GB'] as $unit) {
            if ($value < 1024 || $unit === 'GB') {
                return number_format($value, 1)." {$unit}";
            }

            $value /= 1024;
        }
    }
}

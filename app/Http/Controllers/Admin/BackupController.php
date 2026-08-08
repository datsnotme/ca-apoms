<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function index(BackupService $backups): Response
    {
        return Inertia::render('Backups/Index', [
            'backups' => $backups->list(),
        ]);
    }

    public function store(Request $request, BackupService $backups): RedirectResponse
    {
        try {
            $backup = $backups->create($request->user());

            return redirect()->route('backups.index')->with('success', "Backup {$backup['filename']} created.");
        } catch (RuntimeException $e) {
            return redirect()->route('backups.index')->with('error', 'Backup failed: '.$e->getMessage());
        }
    }

    public function download(string $filename, BackupService $backups): StreamedResponse
    {
        return $backups->download($filename);
    }

    public function restore(Request $request, string $filename, BackupService $backups): RedirectResponse
    {
        try {
            $backups->restore($filename, $request->user());

            return redirect()->route('backups.index')->with('success', "Database restored from {$filename}.");
        } catch (RuntimeException $e) {
            return redirect()->route('backups.index')->with('error', 'Restore failed: '.$e->getMessage());
        }
    }
}

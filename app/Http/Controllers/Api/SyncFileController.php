<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The binary half of file/document sync — SyncService::downloadMissingFiles()/
 * uploadChangedFiles() are the only two callers of these routes; row/DB
 * sync (SyncController) never touches these. Same Sanctum-token +
 * sync.manage gating as the rest of routes/api.php.
 *
 * Every path resolution goes through the DB row looked up by uuid — never
 * a client-supplied path — so this can't be used to read or write
 * arbitrary files on disk regardless of what {table}/{uuid} a caller
 * supplies.
 */
class SyncFileController extends Controller
{
    public function download(string $table, string $uuid, SyncService $sync): StreamedResponse|JsonResponse
    {
        $fileConf = $sync->fileColumnFor($table);
        $modelClass = $sync->modelFor($table);

        if (! $fileConf || ! $modelClass) {
            return response()->json(['message' => 'Not a file-bearing synced table.'], 404);
        }

        $model = $modelClass::withTrashed()->where('uuid', $uuid)->first();
        $path = $model?->{$fileConf['column']};

        if (! $path || ! Storage::disk($fileConf['disk'])->exists($path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        return Storage::disk($fileConf['disk'])->response($path);
    }

    public function upload(Request $request, string $table, string $uuid, SyncService $sync): JsonResponse
    {
        $fileConf = $sync->fileColumnFor($table);
        $modelClass = $sync->modelFor($table);

        if (! $fileConf || ! $modelClass) {
            return response()->json(['message' => 'Not a file-bearing synced table.'], 404);
        }

        // The row itself must already exist — file transfer always follows
        // the row-level push/pull that created it, never precedes it.
        if (! $modelClass::withTrashed()->where('uuid', $uuid)->exists()) {
            return response()->json(['message' => 'Unknown entity — sync the row before its file.'], 404);
        }

        $validated = $request->validate(['file' => ['required', 'file']]);

        // The same deterministic uuid-keyed path resolveFileAttributes()
        // already wrote onto the row during the row-level apply — this
        // just has to put bytes where the row already points.
        $localPath = $sync->resolveSyncedFilePath($table, $uuid);
        Storage::disk($fileConf['disk'])->put($localPath, $validated['file']->getContent());

        return response()->json(['ok' => true]);
    }
}

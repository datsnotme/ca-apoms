<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The sync API (routes/api.php). Sanctum-token auth + sync.manage — see
 * ROLE_PERMISSIONS.md. pull()/status() are Phase 2; push() is Phase 3 —
 * both directions share SyncService::applyIncoming() so pull and push can
 * never disagree about what counts as a conflict.
 */
class SyncController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'server_time' => now()->toIso8601String(),
            'authenticated_as' => $request->user()->only(['id', 'name']),
        ]);
    }

    public function pull(Request $request, SyncService $sync): JsonResponse
    {
        $validated = $request->validate([
            'since_id' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $result = $sync->pendingChangesSince(
            $validated['since_id'] ?? 0,
            $validated['limit'] ?? 200,
        );

        return response()->json([
            ...$result,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Receiving side of a push: the caller's device sends its own pending
     * changes (gathered locally via its own pendingChangesSince()), and we
     * run them through the exact same applyIncoming() that pull() uses on
     * the other side — a merge or a conflict is decided identically no
     * matter which direction the data traveled.
     *
     * Wrapped in a transaction — same reasoning as pullFrom()'s own
     * DB::transaction() wrap around this same call — so a batch that fails
     * partway (a malformed change, a DB constraint violation) rolls back
     * atomically instead of leaving some entities updated and others not.
     * Phase 3 already made re-applying a batch safe (version-guarded), but
     * that only helps once a batch either fully lands or fully doesn't;
     * an interrupted transaction guarantees which of those two happened.
     */
    public function push(Request $request, SyncService $sync): JsonResponse
    {
        $validated = $request->validate([
            'changes' => ['present', 'array'],
        ]);

        $device = Device::where('device_code', $request->user()->currentAccessToken()?->name)->first();

        $counts = DB::transaction(fn () => $sync->applyIncoming($validated['changes'], $device?->id));

        $device?->update(['last_sync_at' => now()]);

        return response()->json([
            ...$counts,
            'server_time' => now()->toIso8601String(),
        ]);
    }
}

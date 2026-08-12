<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     */
    public function push(Request $request, SyncService $sync): JsonResponse
    {
        $validated = $request->validate([
            'changes' => ['present', 'array'],
        ]);

        $device = Device::where('device_code', $request->user()->currentAccessToken()?->name)->first();

        $counts = $sync->applyIncoming($validated['changes'], $device?->id);

        return response()->json([
            ...$counts,
            'server_time' => now()->toIso8601String(),
        ]);
    }
}

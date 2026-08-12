<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SyncPullService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The serving side of Phase 2's sync API (routes/api.php). Sanctum-token
 * auth + sync.manage — see ROLE_PERMISSIONS.md. Every route here is read-
 * only in Phase 2; nothing a caller sends is written anywhere yet (that's
 * /api/sync/push, Phase 3).
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

    public function pull(Request $request, SyncPullService $sync): JsonResponse
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
}

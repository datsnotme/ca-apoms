<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SyncRemoteRequest;
use App\Models\Device;
use App\Models\SyncConflict;
use App\Models\SyncRemote;
use App\Models\SyncRun;
use App\Services\SyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 4 of the hybrid sync plan (plans/quirky-popping-parnas.md) — the
 * Sync Center: a status/control dashboard, a manual "Sync Now" action per
 * configured remote, sync history, and conflict resolution. No dedicated
 * Policy — same as BackupController/BrandingController, gated purely by
 * the permission:sync.manage route middleware (see routes/web.php).
 */
class SyncCenterController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Sync/Index', [
            'localDevice' => Device::where('is_local', true)->first(),
            'devices' => Device::orderBy('name')->get(['id', 'name', 'device_code', 'status', 'last_sync_at', 'is_local']),
            'remotes' => SyncRemote::orderBy('name')->get(['id', 'name', 'base_url', 'created_at']),
            'pendingConflictCount' => SyncConflict::where('status', 'pending')->count(),
            'recentRuns' => SyncRun::with('device:id,name')->latest('started_at')->limit(5)->get(),
        ]);
    }

    public function setLocalDevice(Device $device): RedirectResponse
    {
        Device::where('is_local', true)->update(['is_local' => false]);
        $device->update(['is_local' => true]);

        return back()->with('success', "{$device->name} set as this instance's device identity.");
    }

    public function storeRemote(SyncRemoteRequest $request): RedirectResponse
    {
        SyncRemote::create($request->validated());

        return back()->with('success', 'Remote added.');
    }

    public function updateRemote(SyncRemoteRequest $request, SyncRemote $remote): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['token'])) {
            unset($data['token']);
        }

        $remote->update($data);

        return back()->with('success', 'Remote updated.');
    }

    public function destroyRemote(SyncRemote $remote): RedirectResponse
    {
        $remote->delete();

        return back()->with('success', 'Remote removed.');
    }

    public function syncNow(SyncRemote $remote, SyncService $sync): RedirectResponse
    {
        $localDevice = Device::where('is_local', true)->first();

        if (! $localDevice) {
            return back()->with('error', 'Set a local device identity before syncing — see the Devices section below.');
        }

        $result = $sync->reconcile($localDevice, $remote);

        if ($result['pullError'] || $result['pushError']) {
            $messages = array_filter([
                $result['pullError'] ? "Pull failed: {$result['pullError']}" : null,
                $result['pushError'] ? "Push failed: {$result['pushError']}" : null,
            ]);

            return back()->with('error', implode(' ', $messages));
        }

        $pull = $result['pull'];
        $push = $result['push'];
        $summary = sprintf(
            'Synced with %s — pulled: %d created, %d updated, %d conflicts. Pushed: %d created, %d updated, %d conflicts.',
            $remote->name,
            $pull->created_count, $pull->updated_count, $pull->conflict_count,
            $push->created_count, $push->updated_count, $push->conflict_count,
        );

        return back()->with('success', $summary);
    }

    public function history(): Response
    {
        return Inertia::render('Sync/History', [
            'runs' => SyncRun::with('device:id,name')
                ->latest('started_at')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function conflicts(Request $request): Response
    {
        return Inertia::render('Sync/Conflicts', [
            'conflicts' => SyncConflict::query()
                ->when($request->string('status')->trim()->isNotEmpty(), fn ($q) => $q->where('status', $request->string('status')))
                ->when(! $request->filled('status'), fn ($q) => $q->where('status', 'pending'))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only('status'),
        ]);
    }

    public function resolveConflict(Request $request, SyncConflict $conflict, SyncService $sync): RedirectResponse
    {
        $validated = $request->validate([
            'resolution' => ['required', 'in:take_remote,keep_local'],
        ]);

        $sync->applyResolution($conflict, $validated['resolution'], $request->user()->id);

        return back()->with('success', 'Conflict resolved.');
    }
}

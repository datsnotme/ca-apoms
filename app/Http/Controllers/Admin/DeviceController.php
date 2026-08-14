<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeviceRequest;
use App\Models\Device;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Device Management (Phase 6 follow-up to the Sync Center) — registering,
 * editing, revoking, and reissuing tokens for sync devices from the web UI
 * instead of only via `sync:register-device`. Same gating as the rest of
 * Sync Center: `permission:sync.manage`, no dedicated Policy.
 */
class DeviceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Sync/Devices/Index', [
            'devices' => Device::with('owner:id,name,email')->orderBy('name')->paginate(20),
            'eligibleUsers' => User::permission('sync.manage')->orderBy('name')->get(['id', 'name', 'email']),
            // Session-flash, not a shared prop — deliberately Devices-page-
            // specific (a raw bearer token is sensitive) and naturally
            // "shown once": Laravel's flash data survives exactly one
            // subsequent request, the same mechanism as flash.success/
            // error, just read directly here instead of via the global
            // HandleInertiaRequests share.
            'newDeviceToken' => session('new_device_token'),
        ]);
    }

    public function store(DeviceRequest $request): RedirectResponse
    {
        $owner = User::findOrFail($request->validated('owner_user_id'));

        $device = Device::create([
            'device_code' => 'CAAPOMS-'.strtoupper(Str::random(8)),
            'name' => $request->validated('name'),
            'owner_user_id' => $owner->id,
            'role_hint' => $request->validated('role_hint'),
            'status' => 'active',
        ]);

        $token = $owner->createToken($device->device_code, ['sync:read', 'sync:write']);

        return back()
            ->with('new_device_token', $token->plainTextToken)
            ->with('success', "Device \"{$device->name}\" registered.");
    }

    public function update(DeviceRequest $request, Device $device): RedirectResponse
    {
        $device->update([
            'name' => $request->validated('name'),
            'role_hint' => $request->validated('role_hint'),
        ]);

        return back()->with('success', "Device \"{$device->name}\" updated.");
    }

    /**
     * Revokes the device's Sanctum token and marks it revoked. The Device
     * row itself is kept, not deleted — sync_changes/sync_checkpoints/
     * sync_runs all reference it, and revoking should preserve that
     * history, not orphan or cascade-delete it.
     */
    public function revoke(Device $device): RedirectResponse
    {
        $device->owner?->tokens()->where('name', $device->device_code)->delete();
        $device->update(['status' => 'revoked', 'is_local' => false]);

        return back()->with('success', "Device \"{$device->name}\" revoked.");
    }

    /**
     * Issues a fresh token for an existing device — for a lost/compromised
     * token, or to reactivate a previously revoked device. Deletes any
     * existing token of the same name first, so a device never ends up
     * with two live tokens.
     */
    public function reissueToken(Device $device): RedirectResponse
    {
        if (! $device->owner) {
            return back()->with('error', "\"{$device->name}\" has no owner user to issue a token for.");
        }

        $device->owner->tokens()->where('name', $device->device_code)->delete();
        $token = $device->owner->createToken($device->device_code, ['sync:read', 'sync:write']);
        $device->update(['status' => 'active']);

        return back()
            ->with('new_device_token', $token->plainTextToken)
            ->with('success', "New token issued for \"{$device->name}\".");
    }

    public function setLocal(Device $device): RedirectResponse
    {
        Device::where('is_local', true)->update(['is_local' => false]);
        $device->update(['is_local' => true]);

        return back()->with('success', "{$device->name} set as this instance's device identity.");
    }
}

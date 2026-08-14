<?php

use App\Enums\RoleName;
use App\Models\Device;

beforeEach(function () {
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->otherAdmin = userWithRole(RoleName::Administrator->value);
    $this->dean = userWithRole(RoleName::Dean->value);
});

test('a non-admin cannot view or act on any device route', function () {
    $device = Device::create(['device_code' => 'CAAPOMS-1', 'name' => 'One', 'owner_user_id' => $this->admin->id, 'status' => 'active']);

    $this->actingAs($this->dean)->get('/sync/devices')->assertForbidden();
    $this->actingAs($this->dean)->post('/sync/devices', ['name' => 'X', 'owner_user_id' => $this->admin->id])->assertForbidden();
    $this->actingAs($this->dean)->put("/sync/devices/{$device->id}", ['name' => 'Y'])->assertForbidden();
    $this->actingAs($this->dean)->post("/sync/devices/{$device->id}/revoke")->assertForbidden();
    $this->actingAs($this->dean)->post("/sync/devices/{$device->id}/reissue-token")->assertForbidden();
});

test('an admin can view the devices page with the device list and eligible-user list', function () {
    Device::create(['device_code' => 'CAAPOMS-1', 'name' => 'One', 'owner_user_id' => $this->admin->id, 'status' => 'active']);

    $response = $this->actingAs($this->admin)->get('/sync/devices');

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('Sync/Devices/Index')
        ->has('devices.data', 1)
        ->has('eligibleUsers', 2) // admin + otherAdmin, both have sync.manage
    );
});

test('an admin can register a device, which issues a one-time token and is only shown once', function () {
    $response = $this->actingAs($this->admin)->post('/sync/devices', [
        'name' => 'LAN Hub', 'owner_user_id' => $this->admin->id, 'role_hint' => 'lan-hub',
    ]);

    $response->assertRedirect()->assertSessionHas('success')->assertSessionHas('new_device_token');

    $device = Device::where('name', 'LAN Hub')->firstOrFail();
    expect($device->device_code)->toStartWith('CAAPOMS-');
    expect($device->owner_user_id)->toBe($this->admin->id);
    expect($device->status)->toBe('active');
    expect($this->admin->tokens()->where('name', $device->device_code)->exists())->toBeTrue();

    // The token is a session flash: it survives exactly the one request
    // right after the redirect (the page load that shows the reveal
    // modal)...
    $flashedToken = session('new_device_token');
    expect($flashedToken)->not->toBeNull();
    $next = $this->actingAs($this->admin)->get('/sync/devices');
    $next->assertInertia(fn ($page) => $page->where('newDeviceToken', $flashedToken));

    // ...and is gone on the request after that.
    $again = $this->actingAs($this->admin)->get('/sync/devices');
    $again->assertInertia(fn ($page) => $page->where('newDeviceToken', null));
});

test('registering a device for a user without sync.manage is rejected', function () {
    $response = $this->actingAs($this->admin)->post('/sync/devices', [
        'name' => 'Bad Device', 'owner_user_id' => $this->dean->id,
    ]);

    $response->assertSessionHasErrors('owner_user_id');
    expect(Device::where('name', 'Bad Device')->exists())->toBeFalse();
});

test('an admin can edit a devices name and role hint without touching its owner or token', function () {
    $device = Device::create(['device_code' => 'CAAPOMS-1', 'name' => 'Old Name', 'owner_user_id' => $this->admin->id, 'status' => 'active']);
    $token = $this->admin->createToken($device->device_code, ['sync:read', 'sync:write']);

    $this->actingAs($this->admin)->put("/sync/devices/{$device->id}", [
        'name' => 'New Name', 'role_hint' => 'cloud',
    ])->assertRedirect();

    $device->refresh();
    expect($device->name)->toBe('New Name');
    expect($device->role_hint)->toBe('cloud');
    expect($device->owner_user_id)->toBe($this->admin->id);
    expect($this->admin->tokens()->where('id', $token->accessToken->id)->exists())->toBeTrue();
});

test('revoking a device deletes its token, marks it revoked, and clears is_local, but keeps the record', function () {
    $device = Device::create(['device_code' => 'CAAPOMS-1', 'name' => 'One', 'owner_user_id' => $this->admin->id, 'status' => 'active', 'is_local' => true]);
    $this->admin->createToken($device->device_code, ['sync:read', 'sync:write']);

    $this->actingAs($this->admin)->post("/sync/devices/{$device->id}/revoke")->assertRedirect();

    $device->refresh();
    expect($device->status)->toBe('revoked');
    expect($device->is_local)->toBeFalse();
    expect($this->admin->tokens()->where('name', $device->device_code)->exists())->toBeFalse();
    expect(Device::find($device->id))->not->toBeNull(); // record kept
});

test('reissuing a token deletes the old one and issues a new one, shown once', function () {
    $device = Device::create(['device_code' => 'CAAPOMS-1', 'name' => 'One', 'owner_user_id' => $this->admin->id, 'status' => 'active']);
    $oldToken = $this->admin->createToken($device->device_code, ['sync:read', 'sync:write']);
    $oldTokenId = $oldToken->accessToken->id;

    $response = $this->actingAs($this->admin)->post("/sync/devices/{$device->id}/reissue-token");

    $response->assertRedirect()->assertSessionHas('new_device_token');
    expect($this->admin->tokens()->where('id', $oldTokenId)->exists())->toBeFalse();
    expect($this->admin->tokens()->where('name', $device->device_code)->count())->toBe(1);
});

test('reissuing a token for a device with no owner fails gracefully', function () {
    $device = Device::create(['device_code' => 'CAAPOMS-ORPHAN', 'name' => 'Orphan', 'owner_user_id' => null, 'status' => 'active']);

    $response = $this->actingAs($this->admin)->post("/sync/devices/{$device->id}/reissue-token");

    $response->assertRedirect()->assertSessionHas('error');
    $response->assertSessionMissing('new_device_token');
});

test('set-local (moved to DeviceController) still works and unsets any previous local device', function () {
    $first = Device::create(['device_code' => 'CAAPOMS-1', 'name' => 'One', 'owner_user_id' => $this->admin->id, 'status' => 'active', 'is_local' => true]);
    $second = Device::create(['device_code' => 'CAAPOMS-2', 'name' => 'Two', 'owner_user_id' => $this->admin->id, 'status' => 'active']);

    $this->actingAs($this->admin)->post("/sync/devices/{$second->id}/set-local")->assertRedirect();

    expect($first->refresh()->is_local)->toBeFalse();
    expect($second->refresh()->is_local)->toBeTrue();
});

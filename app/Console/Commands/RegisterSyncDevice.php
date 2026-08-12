<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Phase 2's device-registration path — a console command, not a UI, since
 * Device Management (spec §32) is Phase 4/6 scope. Issues a Sanctum token
 * scoped to sync abilities only, tied to the given user's own account, so
 * every existing Policy/permission check still applies to whatever the
 * token is later used for — a device token is never a bypass.
 */
class RegisterSyncDevice extends Command
{
    protected $signature = 'sync:register-device {email : Email of the user this device authenticates as} {name : A human-readable device name, e.g. "Admin LAN Hub"}';

    protected $description = 'Register a sync device and issue it a Sanctum token for the sync API.';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("No user found with email {$this->argument('email')}.");

            return self::FAILURE;
        }

        if (! $user->can('sync.manage')) {
            $this->error("{$user->email} does not have the sync.manage permission — issuing a token for them would be a token nobody can actually use against the sync API.");

            return self::FAILURE;
        }

        $device = Device::create([
            'device_code' => 'CAAPOMS-'.strtoupper(Str::random(8)),
            'name' => $this->argument('name'),
            'owner_user_id' => $user->id,
            'status' => 'active',
        ]);

        $token = $user->createToken($device->device_code, ['sync:read', 'sync:write']);

        $this->info("Device registered: {$device->device_code} ({$device->name})");
        $this->newLine();
        $this->line('Token (shown once — copy it now):');
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }
}

<?php

namespace App\Http\Controllers\Operations;

use App\Enums\EquipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\EquipmentMaintenanceRequest;
use App\Models\Equipment;
use App\Models\EquipmentMaintenance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class EquipmentMaintenanceController extends Controller
{
    public function store(EquipmentMaintenanceRequest $request, Equipment $equipment): RedirectResponse
    {
        if ($equipment->status !== EquipmentStatus::Available) {
            return back()->withErrors(['equipment' => 'This equipment is not currently available to send for maintenance.']);
        }

        DB::transaction(function () use ($request, $equipment) {
            $equipment->maintenanceRecords()->create([
                ...$request->validated(),
                'started_at' => $request->validated('started_at') ?? now()->toDateString(),
                'recorded_by' => $request->user()->id,
            ]);

            $equipment->update(['status' => EquipmentStatus::UnderMaintenance]);
        });

        return back()->with('success', 'Maintenance reported.');
    }

    public function complete(EquipmentMaintenanceRequest $request, Equipment $equipment, EquipmentMaintenance $maintenance): RedirectResponse
    {
        abort_unless($maintenance->equipment_id === $equipment->id, 404);

        if ($maintenance->completed_at !== null) {
            return back()->withErrors(['equipment' => 'This maintenance record is already marked complete.']);
        }

        DB::transaction(function () use ($request, $equipment, $maintenance) {
            $maintenance->update([...$request->validated(), 'completed_at' => now()->toDateString()]);

            $equipment->update(['status' => EquipmentStatus::Available]);
        });

        return back()->with('success', 'Maintenance marked complete.');
    }
}

<?php

namespace App\Http\Controllers\Operations;

use App\Enums\EquipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\EquipmentReturnRequest;
use App\Models\Equipment;
use App\Models\EquipmentBorrowing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class EquipmentReturnController extends Controller
{
    public function store(EquipmentReturnRequest $request, Equipment $equipment, EquipmentBorrowing $borrowing): RedirectResponse
    {
        abort_unless($borrowing->equipment_id === $equipment->id, 404);

        if ($borrowing->return()->exists()) {
            return back()->withErrors(['equipment' => 'This borrowing has already been returned.']);
        }

        DB::transaction(function () use ($request, $equipment, $borrowing) {
            $borrowing->return()->create([
                ...$request->validated(),
                'returned_at' => now(),
                'recorded_by' => $request->user()->id,
            ]);

            $equipment->update(['status' => EquipmentStatus::Available]);
        });

        return back()->with('success', 'Return recorded.');
    }
}

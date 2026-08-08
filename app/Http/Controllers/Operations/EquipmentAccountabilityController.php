<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\EquipmentBorrowing;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only report: every currently-unreturned borrowing, i.e. who is
 * accountable for what equipment right now. Computed on demand from
 * equipment_borrowings/equipment_returns — there is no persisted
 * accountabilities table. See ASSUMPTIONS.md.
 */
class EquipmentAccountabilityController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Equipment::class);

        $user = $request->user();

        $outstanding = EquipmentBorrowing::query()
            ->whereDoesntHave('return')
            ->whereHas('equipment', fn ($q) => $q->visibleTo($user))
            ->with(['equipment:id,name,type,department_id', 'equipment.department:id,name', 'borrowedBy:id,name', 'recordedBy:id,name'])
            ->orderBy('borrowed_at')
            ->get()
            ->map(fn (EquipmentBorrowing $borrowing) => [
                ...$borrowing->only(['id', 'borrowed_at', 'expected_return_at', 'purpose']),
                'is_overdue' => $borrowing->expected_return_at !== null && $borrowing->expected_return_at->isPast(),
                'equipment' => [
                    ...$borrowing->equipment->only(['id', 'name', 'type']),
                    'department' => $borrowing->equipment->department,
                ],
                'borrowed_by' => $borrowing->borrowedBy,
                'recorded_by' => $borrowing->recordedBy,
            ]);

        return Inertia::render('Equipment/Accountability', [
            'outstanding' => $outstanding,
        ]);
    }
}

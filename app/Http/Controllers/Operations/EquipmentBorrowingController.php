<?php

namespace App\Http\Controllers\Operations;

use App\Enums\EquipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\EquipmentBorrowingRequest;
use App\Models\Equipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class EquipmentBorrowingController extends Controller
{
    public function store(EquipmentBorrowingRequest $request, Equipment $equipment): RedirectResponse
    {
        DB::transaction(function () use ($request, $equipment) {
            $equipment->borrowings()->create([
                ...$request->validated(),
                'borrowed_at' => now(),
                'recorded_by' => $request->user()->id,
            ]);

            $equipment->update(['status' => EquipmentStatus::Borrowed]);
        });

        return back()->with('success', 'Borrowing recorded.');
    }
}

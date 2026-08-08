<?php

namespace App\Http\Controllers\Operations;

use App\Enums\EquipmentStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\EquipmentRequest;
use App\Models\Department;
use App\Models\Equipment;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EquipmentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Equipment::class);

        $user = $request->user();

        $equipment = Equipment::query()
            ->visibleTo($user)
            ->with(['department:id,name', 'facility:id,name'])
            ->when($request->string('status')->trim()->isNotEmpty(), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('name')
            ->paginate(15)
            ->through(fn (Equipment $item) => [
                ...$item->only(['id', 'name', 'type', 'serial_number']),
                'status' => $item->status->value,
                'status_label' => $item->status->label(),
                'department' => $item->department,
                'facility' => $item->facility,
            ])
            ->withQueryString();

        return Inertia::render('Equipment/Index', [
            'equipment' => $equipment,
            'canCreate' => $user->can('create', Equipment::class),
            'filters' => ['status' => $request->string('status')->toString()],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Equipment::class);

        return Inertia::render('Equipment/Create', $this->formOptions($request));
    }

    public function store(EquipmentRequest $request): RedirectResponse
    {
        $equipment = Equipment::create([...$request->validated(), 'created_by' => $request->user()->id]);

        return redirect()->route('equipment.show', $equipment)->with('success', 'Equipment registered.');
    }

    public function show(Request $request, Equipment $equipment): Response
    {
        $this->authorize('view', $equipment);

        $user = $request->user();

        $equipment->load([
            'department:id,name',
            'facility:id,name',
            'createdBy:id,name',
            'activeBorrowing.borrowedBy:id,name',
            'borrowings' => fn ($q) => $q->with(['borrowedBy:id,name', 'recordedBy:id,name', 'return.recordedBy:id,name'])->orderByDesc('borrowed_at'),
            'maintenanceRecords' => fn ($q) => $q->with('recordedBy:id,name')->orderByDesc('started_at'),
        ]);

        $canManage = $user->can('update', $equipment);

        return Inertia::render('Equipment/Show', [
            'equipment' => [
                ...$equipment->only(['id', 'name', 'type', 'serial_number', 'description']),
                'status' => $equipment->status->value,
                'status_label' => $equipment->status->label(),
                'department' => $equipment->department,
                'facility' => $equipment->facility,
                'created_by' => $equipment->createdBy,
                'active_borrowing' => $equipment->activeBorrowing ? [
                    ...$equipment->activeBorrowing->only(['id', 'borrowed_at', 'expected_return_at', 'purpose']),
                    'borrowed_by' => $equipment->activeBorrowing->borrowedBy,
                ] : null,
                'borrowings' => $equipment->borrowings->map(fn ($b) => [
                    ...$b->only(['id', 'borrowed_at', 'expected_return_at', 'purpose']),
                    'borrowed_by' => $b->borrowedBy,
                    'return' => $b->return ? [
                        ...$b->return->only(['id', 'returned_at', 'condition_on_return', 'notes']),
                        'recorded_by' => $b->return->recordedBy,
                    ] : null,
                ]),
                'maintenance_records' => $equipment->maintenanceRecords->map(fn ($m) => [
                    ...$m->only(['id', 'description', 'started_at', 'completed_at', 'performed_by', 'notes']),
                    'recorded_by' => $m->recordedBy,
                    'can_complete' => $canManage && $m->completed_at === null,
                ]),
            ],
            'canManage' => $canManage,
            'userOptions' => $canManage ? User::query()->orderBy('surname')->get(['id', 'name']) : [],
        ]);
    }

    public function edit(Request $request, Equipment $equipment): Response
    {
        $this->authorize('update', $equipment);

        return Inertia::render('Equipment/Edit', [
            'equipment' => [
                ...$equipment->only(['id', 'name', 'type', 'department_id', 'facility_id', 'serial_number', 'description']),
                'status' => $equipment->status->value,
            ],
            ...$this->formOptions($request),
        ]);
    }

    public function update(EquipmentRequest $request, Equipment $equipment): RedirectResponse
    {
        $equipment->update($request->validated());

        return redirect()->route('equipment.show', $equipment)->with('success', 'Equipment updated.');
    }

    public function destroy(Equipment $equipment): RedirectResponse
    {
        $this->authorize('delete', $equipment);

        $equipment->delete();

        return redirect()->route('equipment.index')->with('success', 'Equipment removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Request $request): array
    {
        $isAdmin = $request->user()->hasRole(RoleName::Administrator->value);

        return [
            'departments' => $isAdmin ? Department::query()->orderBy('name')->get(['id', 'name']) : [],
            'isAdmin' => $isAdmin,
            'facilities' => Facility::query()->visibleTo($request->user())->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'statuses' => collect(EquipmentStatus::cases())->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()]),
        ];
    }
}

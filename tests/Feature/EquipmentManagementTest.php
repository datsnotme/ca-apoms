<?php

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\Equipment;
use App\Models\EquipmentBorrowing;

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->otherDepartment = Department::factory()->create();
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->dean = userWithRole(RoleName::Dean->value);
    $this->head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $this->faculty = userWithRole(RoleName::Faculty->value, $this->department);
});

test('an admin can register shared, college-wide equipment', function () {
    $response = $this->actingAs($this->admin)->post('/equipment', [
        'name' => 'Digital Microscope',
        'type' => 'Microscope',
        'department_id' => '',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('equipment', [
        'name' => 'Digital Microscope',
        'department_id' => null,
        'status' => 'available',
        'created_by' => $this->admin->id,
    ]);
});

test('a department head can only register equipment scoped to their own department, regardless of input', function () {
    $response = $this->actingAs($this->head)->post('/equipment', [
        'name' => 'Soil Test Kit',
        'type' => 'Laboratory Tool',
        'department_id' => $this->otherDepartment->id,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('equipment', [
        'name' => 'Soil Test Kit',
        'department_id' => $this->department->id,
    ]);
});

test('a dean and a faculty member cannot register equipment', function () {
    $this->actingAs($this->dean)->post('/equipment', [
        'name' => 'X', 'type' => 'Tool', 'department_id' => '',
    ])->assertForbidden();

    $this->actingAs($this->faculty)->post('/equipment', [
        'name' => 'X', 'type' => 'Tool', 'department_id' => '',
    ])->assertForbidden();
});

test('a shared equipment item is visible to everyone, a department item only to that department', function () {
    Equipment::factory()->create(['department_id' => null, 'name' => 'Shared Projector']);
    Equipment::factory()->create(['department_id' => $this->department->id, 'name' => 'Dept Laptop']);
    $otherFaculty = userWithRole(RoleName::Faculty->value, $this->otherDepartment);

    $this->actingAs($this->faculty)->get('/equipment')->assertOk()
        ->assertInertia(fn ($page) => $page->where('equipment.data', fn ($rows) => collect($rows)->pluck('name')->contains('Shared Projector')
            && collect($rows)->pluck('name')->contains('Dept Laptop')));

    $this->actingAs($otherFaculty)->get('/equipment')->assertOk()
        ->assertInertia(fn ($page) => $page->where('equipment.data', fn ($rows) => collect($rows)->pluck('name')->contains('Shared Projector')
            && ! collect($rows)->pluck('name')->contains('Dept Laptop')));
});

// --- Borrowing / return workflow ---

test('recording a borrowing marks the equipment borrowed', function () {
    $item = Equipment::factory()->create(['department_id' => $this->department->id, 'status' => 'available']);

    $response = $this->actingAs($this->head)->post("/equipment/{$item->id}/borrowings", [
        'borrowed_by' => $this->faculty->id,
        'expected_return_at' => now()->addWeek()->format('Y-m-d'),
    ]);

    $response->assertRedirect();
    $item->refresh();
    expect($item->status->value)->toBe('borrowed');
    $this->assertDatabaseHas('equipment_borrowings', ['equipment_id' => $item->id, 'borrowed_by' => $this->faculty->id]);
});

test('equipment that is already borrowed cannot be borrowed again', function () {
    $item = Equipment::factory()->create(['department_id' => $this->department->id, 'status' => 'borrowed']);

    $this->actingAs($this->head)->post("/equipment/{$item->id}/borrowings", [
        'borrowed_by' => $this->faculty->id,
    ])->assertSessionHasErrors('equipment');
});

test('recording a return marks the equipment available again', function () {
    $item = Equipment::factory()->create(['department_id' => $this->department->id, 'status' => 'borrowed']);
    $borrowing = EquipmentBorrowing::factory()->create([
        'equipment_id' => $item->id,
        'borrowed_by' => $this->faculty->id,
        'recorded_by' => $this->head->id,
    ]);

    $response = $this->actingAs($this->head)->post("/equipment/{$item->id}/borrowings/{$borrowing->id}/return", [
        'condition_on_return' => 'Good',
    ]);

    $response->assertRedirect();
    $item->refresh();
    expect($item->status->value)->toBe('available');
    $this->assertDatabaseHas('equipment_returns', ['equipment_borrowing_id' => $borrowing->id]);
});

test('a borrowing that already has a return cannot be returned again', function () {
    $item = Equipment::factory()->create(['department_id' => $this->department->id, 'status' => 'available']);
    $borrowing = EquipmentBorrowing::factory()->create([
        'equipment_id' => $item->id,
        'borrowed_by' => $this->faculty->id,
        'recorded_by' => $this->head->id,
    ]);
    $borrowing->return()->create([
        'returned_at' => now(),
        'recorded_by' => $this->head->id,
    ]);

    $this->actingAs($this->head)->post("/equipment/{$item->id}/borrowings/{$borrowing->id}/return", [])
        ->assertSessionHasErrors('equipment');
});

test('a faculty member cannot record a borrowing or return', function () {
    $item = Equipment::factory()->create(['department_id' => $this->department->id, 'status' => 'available']);

    $this->actingAs($this->faculty)->post("/equipment/{$item->id}/borrowings", [
        'borrowed_by' => $this->faculty->id,
    ])->assertForbidden();
});

// --- Maintenance workflow ---

test('reporting maintenance marks the equipment under maintenance, and completing it restores availability', function () {
    $item = Equipment::factory()->create(['department_id' => $this->department->id, 'status' => 'available']);

    $response = $this->actingAs($this->head)->post("/equipment/{$item->id}/maintenance", [
        'description' => 'Lens needs cleaning',
    ]);
    $response->assertRedirect();

    $item->refresh();
    expect($item->status->value)->toBe('under_maintenance');

    $maintenance = $item->maintenanceRecords()->first();
    $this->assertDatabaseHas('equipment_maintenance', ['id' => $maintenance->id, 'completed_at' => null]);

    $this->actingAs($this->head)->patch("/equipment/{$item->id}/maintenance/{$maintenance->id}/complete", [
        'notes' => 'Cleaned and tested',
    ])->assertRedirect();

    $item->refresh();
    expect($item->status->value)->toBe('available');
    $maintenance->refresh();
    expect($maintenance->completed_at)->not->toBeNull();
});

test('equipment that is borrowed cannot be sent for maintenance', function () {
    $item = Equipment::factory()->create(['department_id' => $this->department->id, 'status' => 'borrowed']);

    $this->actingAs($this->head)->post("/equipment/{$item->id}/maintenance", [
        'description' => 'Not allowed',
    ])->assertSessionHasErrors('equipment');
});

// --- Accountability report ---

test('the accountability report lists only outstanding borrowings, scoped by department visibility', function () {
    $ownItem = Equipment::factory()->create(['department_id' => $this->department->id]);
    $otherItem = Equipment::factory()->create(['department_id' => $this->otherDepartment->id]);

    $outstanding = EquipmentBorrowing::factory()->create([
        'equipment_id' => $ownItem->id,
        'borrowed_by' => $this->faculty->id,
        'recorded_by' => $this->head->id,
    ]);
    $returnedBorrowing = EquipmentBorrowing::factory()->create([
        'equipment_id' => $ownItem->id,
        'borrowed_by' => $this->faculty->id,
        'recorded_by' => $this->head->id,
    ]);
    $returnedBorrowing->return()->create(['returned_at' => now(), 'recorded_by' => $this->head->id]);
    EquipmentBorrowing::factory()->create([
        'equipment_id' => $otherItem->id,
        'borrowed_by' => $this->faculty->id,
        'recorded_by' => $this->head->id,
    ]);

    $response = $this->actingAs($this->head)->get('/equipment/accountability');

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->has('outstanding', 1)
        ->where('outstanding.0.id', $outstanding->id));
});

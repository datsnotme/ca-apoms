<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Facades\DB;

/**
 * Centralizes the student-plus-related-records write path (guardians,
 * emergency contact, addresses) so it isn't duplicated between store()
 * and update() in StudentController.
 */
class StudentService
{
    public function create(array $data, int $createdById): Student
    {
        return DB::transaction(function () use ($data, $createdById) {
            $student = Student::create([
                ...$this->studentFields($data),
                'created_by' => $createdById,
            ]);

            $this->syncGuardians($student, $data);
            $this->syncAddresses($student, $data);

            return $student;
        });
    }

    public function update(Student $student, array $data): Student
    {
        DB::transaction(function () use ($student, $data) {
            $student->statusChangeReason = $data['status_reason'] ?? null;
            $student->update($this->studentFields($data));
            $this->syncGuardians($student, $data);
            $this->syncAddresses($student, $data);
        });

        return $student->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function studentFields(array $data): array
    {
        return collect($data)->except([
            'guardian_name', 'guardian_relationship', 'guardian_contact_number', 'guardian_address',
            'emergency_name', 'emergency_relationship', 'emergency_contact_number', 'emergency_address',
            'permanent_address', 'current_address', 'status_reason',
        ])->all();
    }

    private function syncGuardians(Student $student, array $data): void
    {
        if (! empty($data['guardian_name'])) {
            $student->guardians()->updateOrCreate(['type' => 'guardian'], [
                'name' => $data['guardian_name'],
                'relationship' => $data['guardian_relationship'] ?? null,
                'contact_number' => $data['guardian_contact_number'] ?? null,
                'address' => $data['guardian_address'] ?? null,
            ]);
        } else {
            $student->guardians()->where('type', 'guardian')->delete();
        }

        if (! empty($data['emergency_name'])) {
            $student->guardians()->updateOrCreate(['type' => 'emergency_contact'], [
                'name' => $data['emergency_name'],
                'relationship' => $data['emergency_relationship'] ?? null,
                'contact_number' => $data['emergency_contact_number'] ?? null,
                'address' => $data['emergency_address'] ?? null,
            ]);
        } else {
            $student->guardians()->where('type', 'emergency_contact')->delete();
        }
    }

    private function syncAddresses(Student $student, array $data): void
    {
        if (! empty($data['permanent_address'])) {
            $student->addresses()->updateOrCreate(['type' => 'permanent'], ['address_line' => $data['permanent_address']]);
        } else {
            $student->addresses()->where('type', 'permanent')->delete();
        }

        if (! empty($data['current_address'])) {
            $student->addresses()->updateOrCreate(['type' => 'current'], ['address_line' => $data['current_address']]);
        } else {
            $student->addresses()->where('type', 'current')->delete();
        }
    }
}

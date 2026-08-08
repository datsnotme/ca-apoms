<?php

namespace App\Http\Requests\Operations;

use App\Models\EquipmentMaintenance;
use Illuminate\Foundation\Http\FormRequest;

class EquipmentMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $maintenance = $this->route('maintenance');

        return $maintenance
            ? $this->user()->can('update', $maintenance)
            : $this->user()->can('create', [EquipmentMaintenance::class, $this->route('equipment')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Completing a maintenance record only ever sends `notes` — the
        // description/started_at/performed_by fields are set once, at
        // report time, mirroring MeetingActionItemRequest's create-vs-
        // quick-update field split (Phase 6B).
        $isComplete = (bool) $this->route('maintenance');

        if ($isComplete) {
            return [
                'notes' => ['nullable', 'string', 'max:2000'],
            ];
        }

        return [
            'description' => ['required', 'string', 'max:2000'],
            'started_at' => ['nullable', 'date'],
            'performed_by' => ['nullable', 'string', 'max:150'],
        ];
    }
}

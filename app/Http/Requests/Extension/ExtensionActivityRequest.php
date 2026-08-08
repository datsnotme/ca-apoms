<?php

namespace App\Http\Requests\Extension;

use App\Models\ExtensionActivity;
use Illuminate\Foundation\Http\FormRequest;

class ExtensionActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('extensionProject');

        return $this->user()->can('create', [ExtensionActivity::class, $project]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'activity_type' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'activity_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:150'],
        ];
    }
}

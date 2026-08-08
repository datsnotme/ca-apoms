<?php

namespace App\Http\Requests\Extension;

use App\Models\ExtensionMember;
use App\Models\ExtensionProject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExtensionMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ExtensionProject $project */
        $project = $this->route('extensionProject');

        return $this->user()->can('create', [ExtensionMember::class, $project]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $project = $this->route('extensionProject');

        return [
            'user_id' => [
                'required',
                Rule::exists('users', 'id'),
                Rule::unique('extension_members', 'user_id')->where('extension_project_id', $project->id),
            ],
            'is_lead' => ['sometimes', 'boolean'],
        ];
    }
}

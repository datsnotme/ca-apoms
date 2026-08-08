<?php

namespace App\Http\Requests\Research;

use App\Models\ResearchMember;
use App\Models\ResearchProject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResearchMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ResearchProject $project */
        $project = $this->route('researchProject');

        return $this->user()->can('create', [ResearchMember::class, $project]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $project = $this->route('researchProject');

        return [
            'user_id' => [
                'required',
                Rule::exists('users', 'id'),
                Rule::unique('research_members', 'user_id')->where('research_project_id', $project->id),
            ],
            'is_lead' => ['sometimes', 'boolean'],
        ];
    }
}

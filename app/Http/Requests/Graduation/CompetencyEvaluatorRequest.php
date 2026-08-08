<?php

namespace App\Http\Requests\Graduation;

use App\Enums\RoleName;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompetencyEvaluatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('graduationCandidate'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'evaluator_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($q) => $q->whereNull('deleted_at')),
                function (string $attribute, mixed $value, Closure $fail) {
                    $user = User::find($value);

                    if (! $user) {
                        return;
                    }

                    if (! $user->hasRole(RoleName::Faculty->value)) {
                        $fail('Only faculty members can be assigned as competency evaluators.');

                        return;
                    }

                    $candidate = $this->route('graduationCandidate');
                    if ($user->department_id !== $candidate->student->department_id) {
                        $fail('The evaluator must be from the same department as the candidate.');
                    }
                },
            ],
        ];
    }
}

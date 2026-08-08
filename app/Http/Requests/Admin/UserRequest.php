<?php

namespace App\Http\Requests\Admin;

use App\Enums\ActiveStatus;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user
            ? $this->user()->can('update', $user)
            : $this->user()->can('create', User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'employee_number' => ['required', 'string', 'max:30', Rule::unique('users', 'employee_number')->ignore($userId)],
            'surname' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($userId)],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(RoleName::values())],
            'department_id' => [
                Rule::requiredIf(fn () => in_array($this->input('role'), [
                    RoleName::DepartmentHead->value, RoleName::Faculty->value,
                ], true)),
                'nullable', 'exists:departments,id',
            ],
            'status' => ['required', Rule::enum(ActiveStatus::class)],
        ];
    }
}

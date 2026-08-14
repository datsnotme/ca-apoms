<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DeviceRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'role_hint' => ['nullable', 'string', 'max:255'],
            // Only required (and only meaningful) when registering a new
            // device — editing an existing one never changes who its
            // token authenticates as, since the token itself is already
            // bound to the original owner via Sanctum.
            'owner_user_id' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                'integer',
                Rule::exists('users', 'id'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'owner_user_id.required' => 'Choose which Admin this device authenticates as.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        // Mirrors sync:register-device's own check — issuing a token for a
        // user without sync.manage would be a token nobody can actually
        // use against the sync API.
        $validator->after(function (Validator $validator) {
            if (! $this->filled('owner_user_id')) {
                return;
            }

            $owner = User::find($this->input('owner_user_id'));

            if ($owner && ! $owner->can('sync.manage')) {
                $validator->errors()->add('owner_user_id', "{$owner->name} does not have the sync.manage permission.");
            }
        });
    }
}

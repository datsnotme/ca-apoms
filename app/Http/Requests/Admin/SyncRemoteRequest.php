<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SyncRemoteRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'base_url' => ['required', 'url', 'max:255'],
            // Required when registering a new remote; optional on update —
            // an Admin editing the name/URL shouldn't be forced to re-paste
            // the token every time.
            'token' => [$this->isMethod('post') ? 'required' : 'nullable', 'string'],
        ];
    }
}

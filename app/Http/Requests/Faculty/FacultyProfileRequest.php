<?php

namespace App\Http\Requests\Faculty;

use App\Enums\FacultyEmploymentStatus;
use App\Models\FacultyProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacultyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Lazily materialize the profile row — a faculty member may not have
        // one yet if this is their first edit.
        $profile = FacultyProfile::firstOrCreate(['user_id' => $this->route('user')->id]);

        return $this->user()->can('update', $profile);
    }

    /**
     * A faculty member editing their own profile may only touch a limited
     * set of fields; academic_rank/employment_status/date_hired are
     * HR-controlled and require faculty-profiles.manage (Admin).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'specialization' => ['nullable', 'string', 'max:150'],
            'office_location' => ['nullable', 'string', 'max:150'],
            'bio' => ['nullable', 'string', 'max:2000'],
        ];

        if ($this->user()->can('faculty-profiles.manage')) {
            $rules['academic_rank'] = ['nullable', 'string', 'max:100'];
            $rules['employment_status'] = ['required', Rule::in(array_column(FacultyEmploymentStatus::cases(), 'value'))];
            $rules['date_hired'] = ['nullable', 'date'];
        }

        return $rules;
    }
}

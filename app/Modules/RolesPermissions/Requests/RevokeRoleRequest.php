<?php

namespace App\Modules\RolesPermissions\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Modules\RolesPermissions\Enums\RoleEnum;

class RevokeRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'staff_id' => [
                'required',
                'integer',
                Rule::exists('staff', 'id'),
            ],

            'role' => [
                'required',
                'string',
                Rule::in(RoleEnum::clinicStaffRoles()),
            ],

            'clinic_id' => [
                'nullable',
                'integer',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'staff_id.required' => 'Staff member is required.',
            'staff_id.exists'   => 'Selected staff member does not exist.',
            'role.required'     => 'Role is required.',
            'role.in'           => 'Selected role is not valid.',
        ];
    }
}

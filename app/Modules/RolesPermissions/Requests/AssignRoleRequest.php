<?php

namespace App\Modules\RolesPermissions\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Modules\RolesPermissions\Enums\RoleEnum;

class AssignRoleRequest extends FormRequest
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

            'is_temporary' => [
                'sometimes',
                'boolean',
            ],

            'expires_at' => [
                'nullable',
                'date',
                'after:now',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'staff_id.required' => __('role_permission.validation.staff_required'),
            'staff_id.exists'   => __('role_permission.validation.staff_exists'),

            'role.required'     => __('role_permission.validation.role_required'),
            'role.in'           => __('role_permission.validation.role_invalid'),

            'clinic_id.integer' => __('role_permission.validation.clinic_id_integer'),

            'is_temporary.boolean' => __('role_permission.validation.is_temporary_boolean'),

            'expires_at.date'  => __('role_permission.validation.expires_at_date'),
            'expires_at.after' => __('role_permission.validation.expires_at_after'),

            'notes.string' => __('role_permission.validation.notes_string'),
            'notes.max'    => __('role_permission.validation.notes_max'),
        ];
    }
}

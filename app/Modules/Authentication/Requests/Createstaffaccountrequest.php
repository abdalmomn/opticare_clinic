<?php

namespace App\Modules\Authentication\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Modules\RolesPermissions\Enums\RoleEnum;
use App\Modules\RolesPermissions\Constants\PermissionList;

class CreateStaffAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('staff', 'email'),
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
            ],

            'role' => [
                'required',
                'string',
                Rule::in([
                    RoleEnum::DOCTOR->value,
                    RoleEnum::SECRETARY->value,
                    RoleEnum::IMAGING_TECHNICIAN->value,
                ]),
            ],

            'clinic_id' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'permission_overrides' => [
                'nullable',
                'array',
            ],

            'permission_overrides.*.permission' => [
                'required_with:permission_overrides',
                'string',
                Rule::in(PermissionList::all()),
            ],

            'permission_overrides.*.effect' => [
                'required_with:permission_overrides',
                'string',
                Rule::in(['grant', 'deny']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => __('auth.validation.staff_name_required'),
            'email.required'    => __('auth.validation.email_required'),
            'email.email'       => __('auth.validation.email_valid'),
            'email.unique'      => __('auth.validation.email_unique'),
            'role.required'     => __('auth.validation.role_required'),
            'role.in'           => __('auth.validation.role_invalid'),
            'password.min'      => __('auth.validation.password_min'),
        ];
    }
}

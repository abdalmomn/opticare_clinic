<?php

namespace App\Modules\RolesPermissions\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Modules\RolesPermissions\Constants\PermissionList;

class ClearPermissionOverrideRequest extends FormRequest
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

            'permission' => [
                'required',
                'string',
                Rule::in(PermissionList::all()),
            ],
        ];
    }

    public function messages(): array
    {
    return [
        'staff_id.required' => __('role_permission.validation.staff_required'),
        'staff_id.exists'   => __('role_permission.validation.staff_exists'),

        'permission.required' => __('role_permission.validation.permission_required'),
        'permission.in'       => __('role_permission.validation.permission_invalid'),
    ];
    }
}

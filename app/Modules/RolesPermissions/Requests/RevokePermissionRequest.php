<?php

namespace App\Modules\RolesPermissions\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Modules\RolesPermissions\Constants\PermissionList;

class RevokePermissionRequest extends FormRequest
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

            'permission.required' => __('role_permission.validation.permission_required'),
            'permission.in'       => __('role_permission.validation.permission_invalid'),

            'is_temporary.boolean' => __('role_permission.validation.is_temporary_boolean'),

            'expires_at.date'  => __('role_permission.validation.expires_at_date'),
            'expires_at.after' => __('role_permission.validation.expires_at_after'),

            'notes.string' => __('role_permission.validation.notes_string'),
            'notes.max'    => __('role_permission.validation.notes_max'),
        ];
    }
}

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
}

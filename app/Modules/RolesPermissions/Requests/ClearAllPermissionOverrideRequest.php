<?php

namespace App\Modules\RolesPermissions\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ClearAllPermissionOverrideRequest extends FormRequest
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
        ];
    }
}

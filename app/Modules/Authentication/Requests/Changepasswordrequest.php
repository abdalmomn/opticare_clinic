<?php

namespace App\Modules\Authentication\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password'      => ['required', 'string'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => __('auth.validation.current_password_required'),
            'password.required'         => __('auth.validation.password_required'),
            'password.min'              => __('auth.validation.new_password_min'),
            'password.confirmed'        => __('auth.validation.password_confirmed'),
        ];
    }
}

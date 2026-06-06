<?php

namespace App\Modules\Authentication\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reset_token'           => ['required', 'string'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'reset_token.required' => __('auth.validation.reset_token_required'),
            'password.required'    => __('auth.validation.password_required'),
            'password.min'         => __('auth.validation.password_min'),
            'password.confirmed'   => __('auth.validation.password_confirmed'),
        ];
    }
}

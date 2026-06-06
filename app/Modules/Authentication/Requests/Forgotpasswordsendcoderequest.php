<?php

namespace App\Modules\Authentication\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordSendCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'         => ['required', 'string', 'email'],
            'captcha_token' => [
                config('opticare.captcha_enabled', false) ? 'required' : 'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'         => __('auth.validation.email_required'),
            'email.email'            => __('auth.validation.email_valid'),
            'captcha_token.required' => __('auth.validation.captcha_required'),
        ];
    }
}

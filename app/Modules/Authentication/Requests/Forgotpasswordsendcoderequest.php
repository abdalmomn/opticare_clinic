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
            'email.required'         => 'Email address is required.',
            'email.email'            => 'Please provide a valid email address.',
            'captcha_token.required' => 'Captcha verification is required.',
        ];
    }
}

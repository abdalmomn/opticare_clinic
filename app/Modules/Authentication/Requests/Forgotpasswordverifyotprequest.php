<?php

namespace App\Modules\Authentication\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordVerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'         => ['required', 'string', 'email'],
            'otp'           => ['required', 'string', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'         => 'Email address is required.',
            'otp.required'           => 'OTP code is required.',
            'otp.digits'             => 'OTP code must be exactly 6 digits.',
        ];
    }
}

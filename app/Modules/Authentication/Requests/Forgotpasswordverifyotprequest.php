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
            'email.required' => __('auth.validation.email_required'),
            'email.email'    => __('auth.validation.email_valid'),
            'otp.required'   => __('auth.validation.otp_required'),
            'otp.digits'     => __('auth.validation.otp_digits'),
        ];
    }
}

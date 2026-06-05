<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OptiCare Business Rules Configuration
    |--------------------------------------------------------------------------
    */

    'cancel_deadline_hours' => env('OPTICARE_CANCEL_DEADLINE_HOURS', 24),

    'surgery_confirm_deadline_hours' => env('OPTICARE_SURGERY_CONFIRM_HOURS', 24),

    'otp_resend_base_seconds' => env('OPTICARE_OTP_BASE_SECONDS', 20),

    'is_medical_center' => env('OPTICARE_IS_CENTER', false),

    'imaging_one_at_a_time' => env('OPTICARE_IMAGING_ONE_AT_A_TIME', true),

    'clinic_id' => env('OPTICARE_CLINIC_ID', 1),

    'captcha_enabled' => env('OPTICARE_CAPTCHA_ENABLED', false),
    
    'otp_channel' => env('OPTICARE_OTP_CHANNEL', 'email'),
];

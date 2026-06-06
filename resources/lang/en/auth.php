<?php

return [
    'messages' => [
        'logged_in' => 'Logged in successfully.',
        'logged_out' => 'Logged out successfully.',
        'password_changed' => 'Password changed successfully.',
        'otp_sent' => 'OTP code sent successfully.',
        'otp_verified' => 'OTP verified successfully.',
        'password_reset' => 'Password has been reset successfully. Please login with your new password.',
        'staff_created' => 'Staff account created successfully.',
        'temporary_password_note' => 'This is a one-time temporary password. The staff member must change it upon first login.',
    ],

    'errors' => [
        'invalid_credentials' => 'The provided credentials are incorrect.',
        'account_deactivated' => 'Your account has been deactivated. Please contact your administrator.',
        'current_password_incorrect' => 'The current password is incorrect.',
        'new_password_same_as_current' => 'The new password must be different from the current password.',
        'captcha_failed' => 'Captcha verification failed.',
        'staff_phone_missing' => 'This staff account does not have a phone number.',
        'resend_wait' => 'Please wait :seconds seconds before requesting a new code.',
        'no_valid_otp' => 'No valid OTP request found. Please request a new code.',
        'otp_expired' => 'The OTP code has expired. Please request a new code.',
        'otp_incorrect' => 'The OTP code is incorrect.',
        'invalid_reset_token' => 'Invalid or expired reset token.',
        'staff_not_found' => 'Staff account not found.',
        'unauthorized_create_staff' => 'You are not authorized to create staff accounts.',
        'only_medical_center_admin_can_create_clinic_admin' => 'Only a Medical Center Admin can create a Clinic Admin account.',
        'staff_email_exists' => 'A staff member with this email already exists.',
        'traccar_disabled' => 'Traccar SMS service is disabled.',
        'traccar_not_configured' => 'Traccar SMS service is not configured.',
        'sms_failed' => 'Failed to send SMS message.',
        'unauthenticated' => 'Unauthenticated. Please login first.',
        'forbidden' => 'You are not authorized to perform this action.',
        'not_found' => 'The requested resource was not found.',
        'server_error' => 'Internal server error.',
        'generic_error' => 'An error occurred.',
        'password_reset_required' => 'Your account has been locked after multiple failed login attempts. Please reset your password to continue.',
    ],

    'validation' => [
        'failed' => 'Validation failed.',

        'current_password_required' => 'Current password is required.',
        'password_required' => 'New password is required.',
        'password_min' => 'Password must be at least 8 characters.',
        'new_password_min' => 'New password must be at least 8 characters.',
        'password_confirmed' => 'Password confirmation does not match.',

        'staff_name_required' => 'Staff name is required.',
        'email_required' => 'Email address is required.',
        'email_valid' => 'Please provide a valid email address.',
        'email_unique' => 'A staff member with this email already exists.',
        'role_required' => 'Role is required.',
        'role_invalid' => 'The selected role is not valid for staff account creation.',
        'captcha_required' => 'Captcha verification is required.',
        'otp_required' => 'OTP code is required.',
        'otp_digits' => 'OTP code must be exactly 6 digits.',
        'reset_token_required' => 'Reset token is required.',
    ],

    'emails' => [
        'password_reset' => [
            'subject' => 'OptiCare Password Reset Code',
            'title' => 'Password Reset Code',
            'intro' => 'Your OptiCare password reset code is:',
            'expires' => 'This code will expire shortly. If you did not request this code, please ignore this email.',
            'footer' => 'OptiCare Clinic',
        ],
        'staff_welcome' => [
            'subject' => 'Welcome to OptiCare Clinic',
            'title' => 'Welcome, :name',
            'intro' => 'A staff account has been created for you in OptiCare Clinic.',
            'login_url' => 'Login URL',
            'email' => 'Email',
            'password' => 'Temporary Password',
            'role' => 'Role',
            'clinic_id' => 'Clinic ID',
            'security_note' => 'Please login and change your password as soon as possible. Do not share this password with anyone.',
            'footer' => 'OptiCare Clinic',
        ],
    ],

    'sms' => [
        'password_reset_otp' => 'Your OptiCare password reset code is: :otp',
    ],

    'notes' => [
    'temporary_password' => 'This is a one-time temporary password. The staff member must change it upon first login.',
    'credentials_sent' => 'Login credentials were sent to the staff email address.',
    ],
];

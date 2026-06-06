<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('auth.emails.password_reset.subject') }}</title>
</head>
<body style="font-family: Arial, sans-serif; direction: {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }};">
    <h2>{{ __('auth.emails.password_reset.title') }}</h2>

    <p>{{ __('auth.emails.password_reset.intro') }}</p>

    <h1 style="letter-spacing: 4px;">
        {{ $otp }}
    </h1>

    <p>{{ __('auth.emails.password_reset.expires') }}</p>

    <p>{{ __('auth.emails.password_reset.footer') }}</p>
</body>
</html>

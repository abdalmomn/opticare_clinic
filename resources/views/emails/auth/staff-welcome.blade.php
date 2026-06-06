<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('auth.emails.staff_welcome.subject') }}</title>
</head>
<body style="font-family: Arial, sans-serif; direction: {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }};">
    <h2>{{ __('auth.emails.staff_welcome.title', ['name' => $name]) }}</h2>

    <p>{{ __('auth.emails.staff_welcome.intro') }}</p>
{{--
    <p>
        <strong>{{ __('auth.emails.staff_welcome.login_url') }}:</strong>
        <a href="{{ $loginUrl }}">{{ $loginUrl }}</a>
    </p> --}}

    <p>
        <strong>{{ __('auth.emails.staff_welcome.email') }}:</strong>
        {{ $email }}
    </p>

    <p>
        <strong>{{ __('auth.emails.staff_welcome.password') }}:</strong>
        {{ $temporaryPassword }}
    </p>

    <p>
        <strong>{{ __('auth.emails.staff_welcome.role') }}:</strong>
        {{ $role }}
    </p>

    {{-- @if($clinicId)
        <p>
            <strong>{{ __('auth.emails.staff_welcome.clinic_id') }}:</strong>
            {{ $clinicId }}
        </p>
    @endif --}}

    <p style="color: #b45309;font-weight: bold;">
        {{ __('auth.emails.staff_welcome.security_note') }}
    </p>

</body>
</html>

<?php

namespace App\Modules\Authentication\Services;

use Illuminate\Support\Facades\Http;

class CaptchaService
{
    public function verify(?string $captchaToken, ?string $ip = null, ?string $expectedAction = null): bool
    {
        if (! config('opticare.captcha_enabled', false)) {
            return true;
        }

        if (empty($captchaToken)) {
            return false;
        }

        $secretKey = config('services.recaptcha.secret_key');

        if (empty($secretKey)) {
            return false;
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret'   => $secretKey,
            'response' => $captchaToken,
            'remoteip' => $ip,
        ]);

        if (! $response->ok()) {
            return false;
        }

        $result = $response->json();

        if (($result['success'] ?? false) !== true) {
            return false;
        }

        if ($expectedAction !== null && ($result['action'] ?? null) !== $expectedAction) {
            return false;
        }

        $minScore = (float) config('services.recaptcha.min_score', 0.3);
        $score = (float) ($result['score'] ?? 0);

        return $score >= $minScore;
    }
}

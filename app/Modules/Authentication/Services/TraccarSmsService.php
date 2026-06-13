<?php

namespace App\Modules\Authentication\Services;

use App\Modules\Authentication\Helpers\AuthHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TraccarSmsService
{
    public function send(string $phone, string $message): array
    {
        if (! config('services.traccar_sms.enabled', false)) {
            throw new HttpException(422, __('auth.errors.traccar_disabled'));
        }

        $url = config('services.traccar_sms.url');
        $token = config('services.traccar_sms.token');

        if (empty($url) || empty($token)) {
            throw new HttpException(500, __('auth.errors.traccar_not_configured'));
        }

        $phone = AuthHelper::normalizePhone($phone);

        $response = Http::withHeaders([
                'Authorization' => $token,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ])
            ->timeout(20)
            ->post($url, [
                'to'      => $phone,
                'message' => $message,
            ]);

        if (! $response->successful()) {
            Log::error('[TraccarSms] Failed to send SMS.', [
                'phone'    => $phone,
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);

            throw new HttpException(502, __('auth.errors.traccar_send_failed'));
        }

        return $response->json() ?? [
            'sent' => true,
        ];
    }
}

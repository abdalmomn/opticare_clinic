<?php

namespace App\Modules\Authentication\Services;

use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Authentication\Helpers\AuthHelper;
use App\Modules\Authentication\Events\StaffPasswordResetOtpRequested;
use App\Modules\Authentication\Repositories\StaffRepository;
use App\Modules\Authentication\Repositories\StaffPasswordResetOtpRepository;

class PasswordResetService
{
    public function __construct(
        protected StaffRepository $staffRepository,
        protected StaffPasswordResetOtpRepository $otpRepository,
        protected CaptchaService $captchaService
    ) {}

    public function sendCode(array $data, string $ip, string $userAgent): array
    {
        $email = $data['email'];
        $captchaToken = $data['captcha_token'] ?? null;

        $expectedAction = config('services.recaptcha.expected_action');

        $expectedAction = filled($expectedAction)
            ? $expectedAction
            : null;

        $captchaPassed = $this->captchaService->verify(
            captchaToken: $captchaToken,
            ip: $ip,
            expectedAction: $expectedAction
        );

        if (! $captchaPassed) {
            throw new HttpException(422, __('auth.errors.captcha_failed'));
        }

        $otpChannel = AuthHelper::otpChannel();

        $staff = $this->staffRepository->findActiveByEmail($email);

        if (! $staff) {
            return AuthHelper::genericSendCodeResponse();
        }

        if ($otpChannel === 'sms' && empty($staff->phone)) {
            throw new HttpException(422, __('auth.errors.staff_phone_required'));
        }

        if ($otpChannel === 'both' && empty($staff->phone)) {
            $otpChannel = 'email';
        }

        $existingRecord = $this->otpRepository->findLatestActiveByEmail($email);

        if ($existingRecord && ! $existingRecord->canResend()) {
            $waitSeconds = (int) now()->diffInSeconds(
                $existingRecord->resend_available_at,
                false
            );

            throw new HttpException(
                429,
                __('auth.errors.resend_wait', ['seconds' => $waitSeconds])
            );
        }

        $plainOtp     = AuthHelper::generateOtp(6);
        $otpHash      = AuthHelper::hashOtp($plainOtp);
        $otpExpiresAt = now()->addMinutes(10);

        $captchaHash = $captchaToken
            ? AuthHelper::hashCaptchaToken($captchaToken)
            : null;

        if ($existingRecord) {
            $nextDelay = $existingRecord->nextResendDelaySeconds();
            $resendAvailableAt = now()->addSeconds($nextDelay);

            $this->otpRepository->updateResendData(
                $existingRecord,
                $otpHash,
                $otpExpiresAt,
                $resendAvailableAt
            );

            $record = $existingRecord->fresh();
        } else {
            $this->otpRepository->invalidateOldRecords($email);

            $baseDelay = (int) config('opticare.otp_resend_base_seconds', 20);
            $resendAvailableAt = now()->addSeconds($baseDelay);

            $record = $this->otpRepository->createOtpRecord([
                'staff_id'            => $staff->id,
                'email'               => $email,
                'captcha_token_hash'  => $captchaHash,
                'otp_hash'            => $otpHash,
                'otp_expires_at'      => $otpExpiresAt,
                'resend_count'        => 0,
                'resend_available_at' => $resendAvailableAt,
                'ip_address'          => $ip,
                'user_agent'          => $userAgent,
            ]);
        }

        event(new StaffPasswordResetOtpRequested(
            email: $email,
            otp: $plainOtp,
            phone: in_array($otpChannel, ['sms', 'both'], true) ? $staff->phone : null,
            locale: app()->getLocale()
        ));

        return [
            'resend_available_at' => $record->resend_available_at->toISOString(),
            'otp_channel' => $otpChannel,
        ];
    }

    public function verifyOtp(array $data): array
    {
        $email = $data['email'];
        $otp = $data['otp'];

        $record = $this->otpRepository->findValidOtpRecord($email);

        if (! $record) {
            throw new HttpException(422, __('auth.errors.no_valid_otp'));
        }

        if ($record->isOtpExpired()) {
            throw new HttpException(422, __('auth.errors.otp_expired'));
        }

        if (! AuthHelper::verifyOtp($otp, $record->otp_hash)) {
            throw new HttpException(422, __('auth.errors.otp_incorrect'));
        }

        $plainResetToken = AuthHelper::generateResetToken(64);
        $resetTokenHash = AuthHelper::hashResetToken($plainResetToken);
        $resetTokenExpiry = now()->addMinutes(15);

        $this->otpRepository->markAsVerified(
            $record,
            $resetTokenHash,
            $resetTokenExpiry
        );

        return [
            'reset_token' => $plainResetToken,
        ];
    }

    public function resetPassword(array $data): void
    {
        $resetTokenHash = AuthHelper::hashResetToken($data['reset_token']);

        $record = $this->otpRepository->findVerifiedUnusedByResetTokenHash($resetTokenHash);

        if (! $record) {
            throw new HttpException(422, __('auth.errors.invalid_reset_token'));
        }

        $staff = $this->staffRepository->findActiveByEmail($record->email);

        if (! $staff) {
            throw new HttpException(404, __('auth.errors.staff_not_found'));
        }

        if (Hash::check($data['password'], $staff->password)) {
            throw new HttpException(422, __('auth.errors.new_password_same_as_current'));
        }

        $this->staffRepository->updatePassword(
            $staff,
            Hash::make($data['password'])
        );

        $staff->forceFill([
            'failed_login_attempts' => 0,
            'password_reset_required' => false,
            'password_reset_required_at' => null,
            'password_changed_at' => now(),
        ])->save();

        $this->otpRepository->markAsUsed($record);

        $staff->tokens()->delete();
    }
}

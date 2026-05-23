<?php

namespace App\Modules\Authentication\Services;

use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Authentication\Helpers\AuthHelper;
use App\Modules\Authentication\Repositories\StaffRepository;
use App\Modules\Authentication\Repositories\StaffPasswordResetOtpRepository;
use Illuminate\Support\Facades\Hash;
use App\Modules\Authentication\Events\StaffPasswordResetOtpRequested;
use App\Modules\Authentication\Services\CaptchaService;

class PasswordResetService
{
    public function __construct(
        protected StaffRepository $staffRepository,
        protected StaffPasswordResetOtpRepository $otpRepository,
        protected CaptchaService $captchaService
    ) {}

    public function sendCode(array $data, string $ip, string $userAgent): array
    {
        $email        = $data['email'];
        $captchaToken = $data['captcha_token'] ?? null;

        $captchaPassed = $this->captchaService->verify(
        captchaToken: $captchaToken,
        ip: $ip,
        expectedAction: 'forgot_password'
        );

        if (! $captchaPassed) {
            throw new HttpException(422, 'Captcha verification failed.');
        }

        $staff = $this->staffRepository->findActiveByEmail($email);

        $existingRecord = $this->otpRepository->findLatestActiveByEmail($email);

        if ($existingRecord && ! $existingRecord->canResend()) {
            $waitSeconds = (int) now()->diffInSeconds($existingRecord->resend_available_at, false);

            throw new HttpException(
                429,
                "Please wait {$waitSeconds} seconds before requesting a new code."
            );
        }

        $plainOtp        = AuthHelper::generateOtp(6);
        $otpHash         = AuthHelper::hashOtp($plainOtp);
        $otpExpiresAt    = now()->addMinutes(10);
        $captchaHash = $captchaToken
            ? AuthHelper::hashCaptchaToken($captchaToken)
            : null;

        if ($existingRecord) {
            $nextDelay           = $existingRecord->nextResendDelaySeconds();
            $resendAvailableAt   = now()->addSeconds($nextDelay);

            $this->otpRepository->updateResendData(
                $existingRecord,
                $otpHash,
                $otpExpiresAt,
                $resendAvailableAt
            );

            $record = $existingRecord->fresh();
        } else {
            $this->otpRepository->invalidateOldRecords($email);

            $baseDelay         = (int) config('opticare.otp_resend_base_seconds', 20);
            $resendAvailableAt = now()->addSeconds($baseDelay);

            $record = $this->otpRepository->createOtpRecord([
                'staff_id'           => $staff?->id,
                'email'              => $email,
                'captcha_token_hash' => $captchaHash,
                'otp_hash'           => $otpHash,
                'otp_expires_at'     => $otpExpiresAt,
                'resend_count'       => 0,
                'resend_available_at'=> $resendAvailableAt,
                'ip_address'         => $ip,
                'user_agent'         => $userAgent,
            ]);
        }

        event(new StaffPasswordResetOtpRequested($email, $plainOtp));

        return [
            'resend_available_at' => $record->resend_available_at->toISOString(),
        ];
    }


    public function verifyOtp(array $data): array
    {
        $email = $data['email'];
        $otp   = $data['otp'];

        $record = $this->otpRepository->findValidOtpRecord($email);

        if (! $record) {
            throw new HttpException(422, 'No valid OTP request found. Please request a new code.');
        }

        if ($record->isOtpExpired()) {
            throw new HttpException(422, 'The OTP code has expired. Please request a new code.');
        }

        if (! AuthHelper::verifyOtp($otp, $record->otp_hash)) {
            throw new HttpException(422, 'The OTP code is incorrect.');
        }

        $plainResetToken  = AuthHelper::generateResetToken(64);
        $resetTokenHash   = AuthHelper::hashResetToken($plainResetToken);
        $resetTokenExpiry = now()->addMinutes(15);

        $this->otpRepository->markAsVerified($record, $resetTokenHash, $resetTokenExpiry);

        return [
            'reset_token' => $plainResetToken,
        ];
    }


    public function resetPassword(array $data): void
    {
        $resetTokenHash = AuthHelper::hashResetToken($data['reset_token']);

        $record = $this->otpRepository->findVerifiedUnusedByResetTokenHash($resetTokenHash);

        if (! $record) {
            throw new HttpException(422, 'Invalid or expired reset token.');
        }

        $staff = $this->staffRepository->findActiveByEmail($record->email);

        if (! $staff) {
            throw new HttpException(404, 'Staff account not found.');
        }

        if (Hash::check($data['password'], $staff->password)) {
            throw new HttpException(422, 'The new password must be different from the current password.');
        }

        $this->staffRepository->updatePassword($staff, Hash::make($data['password']));
        $this->otpRepository->markAsUsed($record);

        $staff->tokens()->delete();
    }
}

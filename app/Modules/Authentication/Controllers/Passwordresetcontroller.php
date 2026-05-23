<?php

namespace App\Modules\Authentication\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Modules\Core\Responses\ApiResponse;
use App\Modules\Authentication\Services\PasswordResetService;
use App\Modules\Authentication\Requests\ForgotPasswordSendCodeRequest;
use App\Modules\Authentication\Requests\ForgotPasswordVerifyOtpRequest;
use App\Modules\Authentication\Requests\ResetPasswordRequest;

class PasswordResetController extends Controller
{
    public function __construct(
        protected PasswordResetService $passwordResetService
    ) {}

    public function sendCode(ForgotPasswordSendCodeRequest $request): JsonResponse
    {
        $result = $this->passwordResetService->sendCode(
            data:      $request->validated(),
            ip:        $request->ip(),
            userAgent: $request->userAgent() ?? ''
        );

        return ApiResponse::success(
            data:    $result,
            message: 'OTP code sent successfully.'
        );
    }


    public function verifyOtp(ForgotPasswordVerifyOtpRequest $request): JsonResponse
    {
        $result = $this->passwordResetService->verifyOtp(
            $request->validated()
        );

        return ApiResponse::success(
            data:    $result,
            message: 'OTP verified successfully.'
        );
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $this->passwordResetService->resetPassword(
            $request->validated()
        );

        return ApiResponse::success(
            data:    null,
            message: 'Password has been reset successfully. Please login with your new password.'
        );
    }
}

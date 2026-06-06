<?php

namespace App\Modules\Authentication\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Modules\Core\Responses\ApiResponse;
use App\Modules\Authentication\Services\AuthService;
use App\Modules\Authentication\Requests\LoginRequest;
use App\Modules\Authentication\Requests\ChangePasswordRequest;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}


    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            data:      $request->validated(),
            ip:        $request->ip(),
            userAgent: $request->userAgent() ?? ''
        );

        return ApiResponse::success(
            data:    $result,
            message: __('auth.messages.logged_in')
        );
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout(Auth::user());

        return ApiResponse::success(
            data:    null,
            message: __('auth.messages.logged_out')
        );
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->authService->changePassword(
            staff: Auth::user(),
            data:  $request->validated()
        );

        return ApiResponse::success(
            data:    null,
            message: __('auth.messages.password_changed')
        );
    }
}

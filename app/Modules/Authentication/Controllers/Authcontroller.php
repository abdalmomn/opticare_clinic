<?php
// ══════════════════════════════════════════════════════════════
//  AuthController.php
//  app/Modules/Authentication/Controllers/AuthController.php
// ══════════════════════════════════════════════════════════════

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
            message: 'Logged in successfully.'
        );
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout(Auth::user());

        return ApiResponse::success(
            data:    null,
            message: 'Logged out successfully.'
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
            message: 'Password changed successfully.'
        );
    }
}

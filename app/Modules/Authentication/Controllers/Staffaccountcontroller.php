<?php

namespace App\Modules\Authentication\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Modules\Core\Responses\ApiResponse;
use App\Modules\Authentication\Services\StaffAccountService;
use App\Modules\Authentication\Requests\CreateStaffAccountRequest;
use Illuminate\Support\Facades\Auth;

class StaffAccountController extends Controller
{
    public function __construct(
        protected StaffAccountService $staffAccountService
    ) {}

    public function store(CreateStaffAccountRequest $request): JsonResponse
    {
        $result = $this->staffAccountService->createStaff(
            actor: Auth::user(),
            data:  $request->validated()
        );

        return ApiResponse::created(
            data:    $result,
            message: __('auth.messages.staff_created')
            );
    }
}

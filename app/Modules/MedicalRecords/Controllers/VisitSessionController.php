<?php

namespace App\Modules\MedicalRecords\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Responses\ApiResponse;
use App\Modules\MedicalRecords\Requests\OpenVisitSessionRequest;
use App\Modules\MedicalRecords\Requests\SaveVisitSessionRequest;
use App\Modules\MedicalRecords\Services\VisitSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class VisitSessionController extends Controller
{
    public function __construct(
        protected VisitSessionService $service
    ) {}

    public function show(int $appointment): JsonResponse
    {
        $result = $this->service->showSession($appointment, Auth::user());

        return ApiResponse::success(
            data: $result,
            message: __('medical_record.messages.visit_session_fetched')
        );
    }

    public function open(OpenVisitSessionRequest $request, int $appointment): JsonResponse
    {
        $result = $this->service->openSession($appointment, $request->validated(), Auth::user());

        return ApiResponse::success(
            data: $result,
            message: __('medical_record.messages.visit_session_created')
        );
    }

    public function save(SaveVisitSessionRequest $request, int $visit): JsonResponse
    {
        $result = $this->service->saveSession($visit, $request->validated(), Auth::user());

        return ApiResponse::success(
            data: $result,
            message: __('medical_record.messages.visit_session_saved')
        );
    }

    public function finalize(int $visit): JsonResponse
    {
        $result = $this->service->finalizeSession($visit, Auth::user());

        return ApiResponse::success(
            data: $result,
            message: __('medical_record.messages.visit_finalized')
        );
    }
}

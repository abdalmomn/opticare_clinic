<?php

namespace App\Modules\Imaging\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Responses\ApiResponse;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\Imaging\Requests\ListTechnicianImagingRequestsRequest;
use App\Modules\Imaging\Services\ImagingRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ImagingTechnicianController extends Controller
{
    public function __construct(
        protected ImagingRequestService $service
    ) {}

    public function queue(ListTechnicianImagingRequestsRequest $request): JsonResponse
    {
        $result = $this->service->technicianQueue(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('imaging.messages.queue_fetched')
        );
    }

    public function start(ImagingRequest $imagingRequest): JsonResponse
    {
        $result = $this->service->start(
            $imagingRequest,
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('imaging.messages.request_started')
        );
    }

    public function complete(ImagingRequest $imagingRequest): JsonResponse
    {
        $result = $this->service->complete(
            $imagingRequest,
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('imaging.messages.request_completed')
        );
    }
}

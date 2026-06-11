<?php

namespace App\Modules\Imaging\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Responses\ApiResponse;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\Imaging\Requests\CancelImagingRequestRequest;
use App\Modules\Imaging\Requests\ConfirmImagingPaymentRequest;
use App\Modules\Imaging\Requests\ListImagingRequestsRequest;
use App\Modules\Imaging\Requests\SendImagingRequestToTechnicianRequest;
use App\Modules\Imaging\Requests\StoreImagingRequestRequest;
use App\Modules\Imaging\Services\ImagingRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ImagingRequestController extends Controller
{
    public function __construct(
        protected ImagingRequestService $service
    ) {}

    public function index(ListImagingRequestsRequest $request): JsonResponse
    {
        $result = $this->service->list(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('imaging.messages.requests_fetched')
        );
    }

    public function store(StoreImagingRequestRequest $request): JsonResponse
    {
        $result = $this->service->create(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::created(
            data: $result,
            message: __('imaging.messages.request_created')
        );
    }

    public function show(ImagingRequest $imagingRequest): JsonResponse
    {
        $result = $this->service->show(
            $imagingRequest,
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('imaging.messages.request_fetched')
        );
    }

    public function cancel(CancelImagingRequestRequest $request, ImagingRequest $imagingRequest): JsonResponse
    {
        $result = $this->service->cancel(
            $imagingRequest,
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('imaging.messages.request_cancelled')
        );
    }

    public function confirmPayment(ConfirmImagingPaymentRequest $request, ImagingRequest $imagingRequest): JsonResponse
    {
        $result = $this->service->confirmPayment(
            $imagingRequest,
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('imaging.messages.payment_confirmed')
        );
    }

    public function sendToTechnician(SendImagingRequestToTechnicianRequest $request, ImagingRequest $imagingRequest): JsonResponse
    {
        $result = $this->service->sendToTechnician(
            $imagingRequest,
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('imaging.messages.request_sent_to_technician')
        );
    }
}

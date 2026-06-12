<?php

namespace App\Modules\Imaging\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Clinic\Models\ClinicDevice;
use App\Modules\Core\Responses\ApiResponse;
use App\Modules\Imaging\Requests\ListImagingDevicesRequest;
use App\Modules\Imaging\Requests\StoreImagingDeviceRequest;
use App\Modules\Imaging\Requests\UpdateImagingDeviceRequest;
use App\Modules\Imaging\Services\ImagingDeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ImagingDeviceController extends Controller
{
    public function __construct(
        protected ImagingDeviceService $service
    ) {}

    public function index(ListImagingDevicesRequest $request): JsonResponse
    {
        $result = $this->service->list(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('imaging.messages.devices_fetched')
        );
    }

    public function store(StoreImagingDeviceRequest $request): JsonResponse
    {
        $result = $this->service->create(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::created(
            data: $result,
            message: __('imaging.messages.device_created')
        );
    }

    public function show(ClinicDevice $device): JsonResponse
    {
        $result = $this->service->show(
            $device,
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('imaging.messages.device_fetched')
        );
    }

    public function update(UpdateImagingDeviceRequest $request, ClinicDevice $device): JsonResponse
    {
        $result = $this->service->update(
            $device,
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('imaging.messages.device_updated')
        );
    }

    public function toggleStatus(ClinicDevice $device): JsonResponse
    {
        $result = $this->service->toggleStatus(
            $device,
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('imaging.messages.device_status_toggled')
        );
    }

    public function destroy(ClinicDevice $device): JsonResponse
    {
        $result = $this->service->delete(
            $device,
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: $result['retired_instead']
                ? __('imaging.messages.device_retired_instead')
                : __('imaging.messages.device_deleted')
        );
    }
}

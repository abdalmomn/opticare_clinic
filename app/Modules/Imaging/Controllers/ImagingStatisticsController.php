<?php

namespace App\Modules\Imaging\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Responses\ApiResponse;
use App\Modules\Imaging\Requests\ImagingStatisticsRequest;
use App\Modules\Imaging\Services\ImagingStatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ImagingStatisticsController extends Controller
{
    public function __construct(
        protected ImagingStatisticsService $service
    ) {}

    public function overview(ImagingStatisticsRequest $request): JsonResponse
    {
        $result = $this->service->overview(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('imaging.messages.statistics_fetched')
        );
    }

    public function byDevice(ImagingStatisticsRequest $request): JsonResponse
    {
        $result = $this->service->byDevice(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('imaging.messages.statistics_fetched')
        );
    }

    public function byType(ImagingStatisticsRequest $request): JsonResponse
    {
        $result = $this->service->byType(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('imaging.messages.statistics_fetched')
        );
    }
}

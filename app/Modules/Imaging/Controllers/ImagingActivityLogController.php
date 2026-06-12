<?php

namespace App\Modules\Imaging\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Responses\ApiResponse;
use App\Modules\Imaging\Requests\ListImagingActivityLogsRequest;
use App\Modules\Imaging\Services\ImagingActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ImagingActivityLogController extends Controller
{
    public function __construct(
        protected ImagingActivityLogService $service
    ) {}

    public function index(ListImagingActivityLogsRequest $request): JsonResponse
    {
        $result = $this->service->list(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('imaging.messages.activity_logs_fetched')
        );
    }
}

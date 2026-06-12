<?php

namespace App\Modules\Imaging\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Responses\ApiResponse;
use App\Modules\Imaging\Requests\DirectImagingUploadRequest;
use App\Modules\Imaging\Requests\ExternalImagingUploadRequest;
use App\Modules\Imaging\Services\DirectImagingUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DirectImagingUploadController extends Controller
{
    public function __construct(
        protected DirectImagingUploadService $service
    ) {}

    public function directUpload(DirectImagingUploadRequest $request): JsonResponse
    {
        $result = $this->service->directUpload(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::created(
            data: $result,
            message: __('imaging.messages.direct_upload_completed')
        );
    }

    public function externalUpload(ExternalImagingUploadRequest $request): JsonResponse
    {
        $result = $this->service->externalUpload(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::created(
            data: $result,
            message: __('imaging.messages.external_upload_completed')
        );
    }
}

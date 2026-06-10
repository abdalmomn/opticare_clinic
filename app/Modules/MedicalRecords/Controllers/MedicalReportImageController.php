<?php

namespace App\Modules\MedicalRecords\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Responses\ApiResponse;
use App\Modules\MedicalRecords\Requests\AttachReportImagesRequest;
use App\Modules\MedicalRecords\Services\MedicalReportImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MedicalReportImageController extends Controller
{
    public function __construct(
        protected MedicalReportImageService $service
    ) {}

    public function store(AttachReportImagesRequest $request, int $report): JsonResponse
    {
        $result = $this->service->attachToReport($report, $request->validated(), Auth::user());

        return ApiResponse::success(
            data: $result,
            message: __('medical_record.messages.report_images_attached')
        );
    }
}

<?php

namespace App\Modules\Imaging\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Responses\ApiResponse;
use App\Modules\Imaging\Models\ImagingFile;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\Imaging\Requests\UploadImagingFilesRequest;
use App\Modules\Imaging\Services\ImagingFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ImagingFileController extends Controller
{
    public function __construct(
        protected ImagingFileService $service
    ) {}

    public function store(UploadImagingFilesRequest $request, ImagingRequest $imagingRequest): JsonResponse
    {
        $result = $this->service->uploadToRequest(
            $imagingRequest,
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::created(
            data: $result,
            message: __('imaging.messages.files_uploaded')
        );
    }

    public function destroy(ImagingFile $imagingFile): JsonResponse
    {
        $result = $this->service->deleteFile(
            $imagingFile,
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('imaging.messages.file_deleted')
        );
    }
}

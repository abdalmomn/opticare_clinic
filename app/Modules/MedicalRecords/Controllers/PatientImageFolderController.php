<?php

namespace App\Modules\MedicalRecords\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Responses\ApiResponse;
use App\Modules\MedicalRecords\Requests\FileComparisonRequest;
use App\Modules\MedicalRecords\Requests\FolderFilesRequest;
use App\Modules\MedicalRecords\Requests\ImageComparisonRequest;
use App\Modules\MedicalRecords\Requests\ListImageFoldersRequest;
use App\Modules\MedicalRecords\Requests\ListImageTypesRequest;
use App\Modules\MedicalRecords\Services\PatientImageFolderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PatientImageFolderController extends Controller
{
    public function __construct(
        protected PatientImageFolderService $service
    ) {}


    public function imageTypes(ListImageTypesRequest $request, int $patient): JsonResponse
    {
        $result = $this->service->imageTypes($patient, $request->validated(), Auth::user());

        return ApiResponse::success(
            data: $result,
            message: __('medical_record.messages.image_types_fetched')
        );
    }


    public function imageFolders(ListImageFoldersRequest $request, int $patient): JsonResponse
    {
        $result = $this->service->imageFolders($patient, $request->validated(), Auth::user());

        return ApiResponse::success(
            data: $result,
            message: __('medical_record.messages.image_folders_fetched')
        );
    }


    public function folderFiles(FolderFilesRequest $request, int $folder): JsonResponse
    {
        $result = $this->service->folderFiles($folder, $request->validated(), Auth::user());

        return ApiResponse::success(
            data: $result,
            message: __('medical_record.messages.folder_files_fetched')
        );
    }


    public function imagingFile(int $file): JsonResponse
    {
        $result = $this->service->imagingFile($file, Auth::user());

        return ApiResponse::success(
            data: $result,
            message: __('medical_record.messages.imaging_file_fetched')
        );
    }

    public function imageComparison(ImageComparisonRequest $request, int $patient): JsonResponse
    {
        $result = $this->service->imageComparison($patient, $request->validated(), Auth::user());

        return ApiResponse::success(
            data: $result,
            message: __('medical_record.messages.image_comparison_fetched')
        );
    }

    public function imageComparisonFiles(FileComparisonRequest $request, int $patient): JsonResponse
    {
        $result = $this->service->fileComparison($patient, $request->validated(), Auth::user());

        return ApiResponse::success(
            data: $result,
            message: __('medical_record.messages.image_comparison_fetched')
        );
    }
}

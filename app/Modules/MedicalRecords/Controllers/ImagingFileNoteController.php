<?php

namespace App\Modules\MedicalRecords\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Responses\ApiResponse;
use App\Modules\MedicalRecords\Requests\SaveImageNoteRequest;
use App\Modules\MedicalRecords\Services\ImagingFileNoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ImagingFileNoteController extends Controller
{
    public function __construct(
        protected ImagingFileNoteService $service
    ) {}

    public function store(SaveImageNoteRequest $request, int $file): JsonResponse
    {
        $result = $this->service->saveNote($file, $request->validated(), Auth::user());

        return ApiResponse::success(
            data: $result,
            message: __('medical_record.messages.image_note_saved')
        );
    }
}

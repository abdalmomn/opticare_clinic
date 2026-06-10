<?php

namespace App\Modules\MedicalRecords\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Responses\ApiResponse;
use App\Modules\MedicalRecords\Requests\SearchDiagnosisCodeRequest;
use App\Modules\MedicalRecords\Services\DiagnosisCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DiagnosisCodeController extends Controller
{
    public function __construct(
        protected DiagnosisCodeService $service
    ) {}

    public function index(SearchDiagnosisCodeRequest $request): JsonResponse
    {
        $result = $this->service->search($request->validated(), Auth::user());

        return ApiResponse::success(
            data: $result,
            message: __('medical_record.messages.diagnosis_codes_fetched')
        );
    }
}

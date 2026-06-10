<?php

namespace App\Modules\MedicalRecords\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Responses\ApiResponse;
use App\Modules\MedicalRecords\Requests\ListTimelineRequest;
use App\Modules\MedicalRecords\Services\MedicalRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MedicalRecordController extends Controller
{
    public function __construct(
        protected MedicalRecordService $service
    ) {}

    public function unifiedRecord(int $patient): JsonResponse
    {
        $result = $this->service->unifiedRecord($patient, Auth::user());

        return ApiResponse::success(
            data: $result,
            message: __('medical_record.messages.unified_record_fetched')
        );
    }

    public function visitsTimeline(ListTimelineRequest $request, int $patient): JsonResponse
    {
        $result = $this->service->visitsTimeline($patient, $request->validated(), Auth::user());

        return ApiResponse::success(
            data: $result,
            message: __('medical_record.messages.visits_timeline_fetched')
        );
    }

    public function reportsTimeline(ListTimelineRequest $request, int $patient): JsonResponse
    {
        $result = $this->service->reportsTimeline($patient, $request->validated(), Auth::user());

        return ApiResponse::success(
            data: $result,
            message: __('medical_record.messages.reports_timeline_fetched')
        );
    }

    public function prescriptionsTimeline(ListTimelineRequest $request, int $patient): JsonResponse
    {
        $result = $this->service->prescriptionsTimeline($patient, $request->validated(), Auth::user());

        return ApiResponse::success(
            data: $result,
            message: __('medical_record.messages.prescriptions_timeline_fetched')
        );
    }

    public function measurementsTimeline(ListTimelineRequest $request, int $patient): JsonResponse
    {
        $result = $this->service->measurementsTimeline($patient, $request->validated(), Auth::user());

        return ApiResponse::success(
            data: $result,
            message: __('medical_record.messages.measurements_timeline_fetched')
        );
    }

    public function diagnosesTimeline(ListTimelineRequest $request, int $patient): JsonResponse
    {
        $result = $this->service->diagnosesTimeline($patient, $request->validated(), Auth::user());

        return ApiResponse::success(
            data: $result,
            message: __('medical_record.messages.diagnoses_timeline_fetched')
        );
    }

    public function privateNotesTimeline(ListTimelineRequest $request, int $patient): JsonResponse
    {
        $result = $this->service->privateNotesTimeline($patient, $request->validated(), Auth::user());

        return ApiResponse::success(
            data: $result,
            message: __('medical_record.messages.private_notes_timeline_fetched')
        );
    }

    public function privateNoteDetails(int $note): JsonResponse
    {
        $result = $this->service->privateNoteDetails($note, Auth::user());

        return ApiResponse::success(
            data: $result,
            message: __('medical_record.messages.private_note_fetched')
        );
    }
}

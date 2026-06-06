<?php

namespace App\Modules\Patients\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Responses\ApiResponse;
use App\Modules\Patients\Requests\SearchClinicPatientRequest;
use App\Modules\Patients\Requests\StoreClinicPatientRequest;
use App\Modules\Patients\Requests\UpdateClinicPatientRequest;
use App\Modules\Patients\Services\ClinicPatientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Modules\Patients\Requests\ArchiveClinicPatientRequest;
use App\Modules\Patients\Requests\MarkPatientDeceasedRequest;

class PatientController extends Controller
{
    public function __construct(
        protected ClinicPatientService $service
    ) {}

    public function index(SearchClinicPatientRequest $request): JsonResponse
    {
        $result = $this->service->listPatients(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('patient.messages.patients_fetched')
        );
    }

    public function search(SearchClinicPatientRequest $request): JsonResponse
    {
        $result = $this->service->searchPatients(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('patient.messages.patients_fetched')
        );
    }

    public function store(StoreClinicPatientRequest $request): JsonResponse
    {
        $result = $this->service->createPatient(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::created(
            data: $result,
            message: __('patient.messages.patient_created')
        );
    }

    public function show(int $patient): JsonResponse
    {
        $result = $this->service->showPatient(
            $patient,
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('patient.messages.patient_fetched')
        );
    }

    public function update(UpdateClinicPatientRequest $request, int $patient): JsonResponse
    {
        $result = $this->service->updatePatient(
            $patient,
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('patient.messages.patient_updated')
        );
    }

    public function toggleStatus(int $patient): JsonResponse
    {
        $result = $this->service->toggleStatus(
            $patient,
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('patient.messages.patient_status_updated')
        );
    }

    public function archive(ArchiveClinicPatientRequest $request, int $patient): JsonResponse
    {
        $result = $this->service->archivePatient(
            $patient,
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('patient.messages.patient_archived')
        );
    }

    public function restore(int $patient): JsonResponse
    {
        $result = $this->service->restorePatient(
            $patient,
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('patient.messages.patient_restored')
        );
    }

    public function markDeceased(MarkPatientDeceasedRequest $request, int $patient): JsonResponse
    {
        $result = $this->service->markPatientDeceased(
            $patient,
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('patient.messages.patient_marked_deceased')
        );
    }
}

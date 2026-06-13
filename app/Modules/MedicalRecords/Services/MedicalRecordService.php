<?php

namespace App\Modules\MedicalRecords\Services;

use App\Modules\Authentication\Models\Staff;
use App\Modules\MedicalRecords\Helpers\MedicalRecordHelper;
use App\Modules\MedicalRecords\Repositories\DoctorPrivateNoteRepository;
use App\Modules\MedicalRecords\Repositories\EyeMeasurementRepository;
use App\Modules\MedicalRecords\Repositories\MedicalRecordRepository;
use App\Modules\MedicalRecords\Repositories\MedicalReportRepository;
use App\Modules\MedicalRecords\Repositories\PrescriptionRepository;
use App\Modules\MedicalRecords\Repositories\VisitRecordRepository;
use App\Modules\Patients\Repositories\ClinicPatientRepository;
use App\Modules\RolesPermissions\Constants\PermissionList;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\MedicalRecords\Models\VisitRecord;
use App\Modules\MedicalRecords\Models\MedicalReport;
use App\Modules\MedicalRecords\Models\EyeMeasurement;
use App\Modules\MedicalRecords\Models\DoctorPrivateNote;
use App\Modules\MedicalRecords\Models\Prescription;
use App\Modules\Patients\Models\ClinicPatient;

class MedicalRecordService
{
    public function __construct(
        protected MedicalRecordRepository $records,
        protected ClinicPatientRepository $patients,
        protected EyeMeasurementRepository $measurements,
        protected VisitRecordRepository $visits,
        protected MedicalReportRepository $reports,
        protected PrescriptionRepository $prescriptions,
        protected DoctorPrivateNoteRepository $privateNotes,
    ) {}

    public function unifiedRecord(int $patientId, Staff $actor): array
    {
        MedicalRecordHelper::authorize($actor, PermissionList::VIEW_MEDICAL_RECORDS, 'medical_record.errors.not_allowed_view_record');

        $patient = $this->findPatientOrFail($patientId);

        MedicalRecordHelper::ensurePatientAccessible($actor, $patient->id);

        $record = $this->records->findByPatientId($patient->id);
        $latestMeasurement = $this->measurements->latestForPatient($patient->id);

        return [
            'patient' => [
                'id' => $patient->id,
                'medical_file_number' => $patient->medical_file_number,
                'full_name' => $patient->full_name,
                'gender' => $patient->gender,
                'birth_date' => optional($patient->birth_date)->toDateString(),
                'age' => $patient->birth_date ? $patient->birth_date->age : null,
                'status' => $patient->status,
            ],
            'record' => [
                'last_visit' => $record ? [
                    'id' => $record->last_visit_id,
                    'date' => optional($record->last_visit_at)->toDateTimeString(),
                    'status' => $record->lastVisit?->status,
                ] : null,
                'summary' => $record?->summary,
            ],
            'latest_eye_measurement' => MedicalRecordHelper::formatEyeMeasurement($latestMeasurement),
        ];
    }

    public function visitsTimeline(int $patientId, array $filters, Staff $actor): array
    {
        $patient = $this->prepareTimeline($actor, $patientId, PermissionList::VIEW_VISIT_TIMELINE);

        $paginator = $this->visits->visitsTimeline($patient->id, $filters);

        $paginator->getCollection()->transform(function (VisitRecord $visit): array {
            return MedicalRecordHelper::formatVisitTimelineItem($visit);
        });

        return MedicalRecordHelper::formatPaginated($paginator);
    }

    public function reportsTimeline(int $patientId, array $filters, Staff $actor): array
    {
        $patient = $this->prepareTimeline($actor, $patientId, PermissionList::VIEW_REPORTS);

        $paginator = $this->reports->timeline($patient->id, $filters);

        $paginator->getCollection()->transform(function (MedicalReport $report): array {
            return MedicalRecordHelper::formatReportTimelineItem($report);
        });

        return MedicalRecordHelper::formatPaginated($paginator);
    }

    public function prescriptionsTimeline(int $patientId, array $filters, Staff $actor): array
    {
        $patient = $this->prepareTimeline($actor, $patientId, PermissionList::VIEW_PRESCRIPTIONS);

        $paginator = $this->prescriptions->timeline($patient->id, $filters);

        $paginator->getCollection()->transform(function (Prescription $prescription): array {
            return MedicalRecordHelper::formatPrescriptionTimelineItem($prescription);
        });

        return MedicalRecordHelper::formatPaginated($paginator);
    }

    public function measurementsTimeline(int $patientId, array $filters = []): array
    {
        $paginator = $this->measurements->timeline($patientId, $filters);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (EyeMeasurement $measurement) => MedicalRecordHelper::formatMeasurementTimelineItem($measurement))
                ->values(),
            'pagination' => MedicalRecordHelper::formatPagination($paginator),
        ];
    }


    public function diagnosesTimeline(int $patientId, array $filters, Staff $actor): array
    {
        $patient = $this->prepareTimeline($actor, $patientId, PermissionList::VIEW_DIAGNOSES);

        $paginator = $this->visits->diagnosesTimeline($patient->id, $filters);

        $paginator->getCollection()->transform(function (VisitRecord $visit): array {
            return MedicalRecordHelper::formatDiagnosisTimelineItem($visit);
        });

        return MedicalRecordHelper::formatPaginated($paginator);
    }

    public function privateNotesTimeline(int $patientId, array $filters, Staff $actor): array
    {
        $patient = $this->prepareTimeline($actor, $patientId, PermissionList::VIEW_OWN_NOTES, 'medical_record.errors.not_allowed_view_notes');

        $paginator = $this->privateNotes->timelineForDoctor($patient->id, $actor->id, $filters);

        $paginator->getCollection()->transform(function (DoctorPrivateNote $note): array {
            return MedicalRecordHelper::formatPrivateNoteTimelineItem($note);
        });

        return MedicalRecordHelper::formatPaginated($paginator);
    }

    public function privateNoteDetails(int $noteId, Staff $actor): array
    {
        MedicalRecordHelper::authorize($actor, PermissionList::VIEW_OWN_NOTES, 'medical_record.errors.not_allowed_view_notes');

        $note = $this->privateNotes->findForDoctor($noteId, $actor->id);

        if (! $note) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('medical_record.errors.private_note_not_found')
            );
        }

        return MedicalRecordHelper::formatPrivateNoteDetails($note);
    }


    private function findPatientOrFail(int $patientId): ClinicPatient
    {
        $patient = $this->patients->findPatientById($patientId);

        if (! $patient) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('medical_record.errors.patient_not_found')
            );
        }

        return $patient;
    }

    private function prepareTimeline(
        Staff $actor,
        int $patientId,
        string $permission,
        string $messageKey = 'medical_record.errors.not_allowed_view_timeline'
    ): ClinicPatient {
        MedicalRecordHelper::authorize($actor, $permission, $messageKey);

        $patient = $this->findPatientOrFail($patientId);

        MedicalRecordHelper::ensurePatientAccessible($actor, $patient->id);

        return $patient;
    }
}

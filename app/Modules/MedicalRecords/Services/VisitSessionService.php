<?php

namespace App\Modules\MedicalRecords\Services;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Appointments\Repositories\AppointmentRepository;
use App\Modules\Authentication\Models\Staff;
use App\Modules\MedicalRecords\Helpers\MedicalRecordVisitsHelper;
use App\Modules\MedicalRecords\Models\VisitRecord;
use App\Modules\MedicalRecords\Repositories\DoctorPrivateNoteRepository;
use App\Modules\MedicalRecords\Repositories\EyeMeasurementRepository;
use App\Modules\MedicalRecords\Repositories\MedicalRecordRepository;
use App\Modules\MedicalRecords\Repositories\MedicalReportRepository;
use App\Modules\MedicalRecords\Repositories\PrescriptionRepository;
use App\Modules\MedicalRecords\Repositories\VisitDiagnosisCodeRepository;
use App\Modules\MedicalRecords\Repositories\VisitRecordRepository;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Enums\RoleEnum;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Str;

class VisitSessionService
{
    public function __construct(
        protected VisitRecordRepository $visits,
        protected MedicalRecordRepository $records,
        protected EyeMeasurementRepository $measurements,
        protected MedicalReportRepository $reports,
        protected PrescriptionRepository $prescriptions,
        protected VisitDiagnosisCodeRepository $diagnosisCodes,
        protected DoctorPrivateNoteRepository $privateNotes,
        protected AppointmentRepository $appointments,
        protected MedicalReportImageService $reportImages,
    ) {}

    public function showSession(int $appointmentId, Staff $actor): array
    {
        MedicalRecordVisitsHelper::authorize($actor, PermissionList::VIEW_MEDICAL_RECORDS, 'medical_record.errors.not_allowed_view_record');

        $appointment = $this->findAppointmentOrFail($appointmentId);

        $visit = $this->visits->findByAppointmentId($appointment->id);

        if (! $visit) {
            return [
                'visit_session' => null,
            ];
        }

        $session = $this->visits->findSession($visit->id, $actor->id);

        if (! $session) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('medical_record.errors.visit_not_found')
            );
        }

        return [
            'visit_session' => MedicalRecordVisitsHelper::formatVisitSession($session, $actor),
        ];
    }

    public function openSession(int $appointmentId, array $data, Staff $actor): array
    {
        MedicalRecordVisitsHelper::authorize($actor, PermissionList::CREATE_VISIT_RECORD, 'medical_record.errors.not_allowed_create_visit');

        $appointment = $this->findAppointmentOrFail($appointmentId);

        $existing = $this->visits->findByAppointmentId($appointment->id);

        if ($existing) {
            $session = $this->visits->findSession($existing->id, $actor->id);

            if (! $session) {
                throw new HttpException(
                    Response::HTTP_NOT_FOUND,
                    __('medical_record.errors.visit_not_found')
                );
            }

            return [
                'visit_session' => MedicalRecordVisitsHelper::formatVisitSession($session, $actor),
            ];
        }

        MedicalRecordVisitsHelper::ensureAppointmentReadyForVisit($appointment, $actor);

        $visit = $this->visits->createVisit([
            'patient_id' => $appointment->patient_id,
            'appointment_id' => $appointment->id,
            'doctor_id' => $appointment->doctor_id,
            'status' => VisitRecord::STATUS_DRAFT,
            'visit_type' => $data['visit_type'] ?? VisitRecord::TYPE_CONSULTATION,
            'visit_at' => now(),
            'notes' => $data['notes'] ?? null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $session = $this->visits->findSession($visit->id, $actor->id);

        if (! $session) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('medical_record.errors.visit_not_found')
            );
        }

        return [
            'visit_session' => MedicalRecordVisitsHelper::formatVisitSession($session, $actor),
        ];
    }

    public function saveSession(int $visitId, array $data, Staff $actor): array
    {
        MedicalRecordVisitsHelper::authorize($actor, PermissionList::CREATE_VISIT_RECORD, 'medical_record.errors.not_allowed_save_session');

        $visit = $this->findEditableVisitOrFail($visitId, $actor);

        $doctorId = $visit->doctor_id ?? $actor->id;

        DB::transaction(function () use ($visit, $data, $actor, $doctorId) {
            $visitPayload = MedicalRecordVisitsHelper::extractVisitFields($data['visit'] ?? []);
            $visitPayload['updated_by'] = $actor->id;
            $this->visits->updateVisit($visit, $visitPayload);

            if (MedicalRecordVisitsHelper::present($data, 'eye_measurement')) {
                MedicalRecordVisitsHelper::requirePermission($actor, PermissionList::CREATE_MEASUREMENT, 'medical_record.errors.not_allowed_create_measurement');

                $m = $data['eye_measurement'];
                $this->measurements->updateOrCreateForVisit($visit->id, [
                    'patient_id' => $visit->patient_id,
                    'appointment_id' => $visit->appointment_id,
                    'doctor_id' => $doctorId,
                    'visual_acuity_od' => $m['visual_acuity_od'] ?? null,
                    'visual_acuity_os' => $m['visual_acuity_os'] ?? null,
                    'iop_od' => $m['iop_od'] ?? null,
                    'iop_os' => $m['iop_os'] ?? null,
                    'notes' => $m['notes'] ?? null,
                    'measured_at' => $m['measured_at'] ?? now(),
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);
            }

            if (MedicalRecordVisitsHelper::present($data, 'report')) {
                MedicalRecordVisitsHelper::requirePermission($actor, PermissionList::CREATE_REPORT, 'medical_record.errors.not_allowed_create_report');

                $r = $data['report'];
                $report = $this->reports->updateOrCreateForVisit($visit->id, [
                    'patient_id' => $visit->patient_id,
                    'doctor_id' => $doctorId,
                    'title' => $r['title'] ?? null,
                    'report_text' => $r['report_text'] ?? null,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);

                if (array_key_exists('images', $r)) {
                    $images = array_map(fn ($img) => [
                        'imaging_request_id' => $img['imaging_request_id'] ?? null,
                        'imaging_file_id' => $img['imaging_file_id'] ?? null,
                        'notes' => $img['notes'] ?? null,
                    ], $r['images'] ?? []);

                    $this->reports->replaceImages($report, $images);
                }

                if (! empty($r['selected_image_ids']) || ! empty($r['selected_folder_ids'])) {
                    $this->reportImages->linkSelections(
                        $report,
                        $r['selected_image_ids'] ?? [],
                        $r['selected_folder_ids'] ?? [],
                        'append'
                    );
                }
            }

            if (MedicalRecordVisitsHelper::present($data, 'prescription')) {
                MedicalRecordVisitsHelper::requirePermission($actor, PermissionList::CREATE_PRESCRIPTION, 'medical_record.errors.not_allowed_create_prescription');

                $p = $data['prescription'];
                $prescription = $this->prescriptions->updateOrCreateForVisit($visit->id, [
                    'patient_id' => $visit->patient_id,
                    'doctor_id' => $doctorId,
                    'prescription_text' => $p['prescription_text'] ?? null,
                    'notes' => $p['notes'] ?? null,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);

                if (array_key_exists('items', $p)) {
                    $items = array_map(fn ($item) => [
                        'medicine_name' => $item['medicine_name'],
                        'dosage' => $item['dosage'] ?? null,
                        'frequency' => $item['frequency'] ?? null,
                        'duration' => $item['duration'] ?? null,
                    ], $p['items'] ?? []);

                    $this->prescriptions->replaceItems($prescription, $items);
                }
            }

            if (array_key_exists('diagnosis_codes', $data) && is_array($data['diagnosis_codes'])) {
                MedicalRecordVisitsHelper::requirePermission($actor, PermissionList::ADD_DISEASE_CLASSIFICATION, 'medical_record.errors.not_allowed_add_disease_classification');

                $this->diagnosisCodes->syncForVisit(
                    $visit->id,
                    $visit->patient_id,
                    $doctorId,
                    $actor->id,
                    $data['diagnosis_codes']
                );
            }

            if (MedicalRecordVisitsHelper::present($data, 'private_note') && ! empty($data['private_note']['note'])) {
                MedicalRecordVisitsHelper::requirePermission($actor, PermissionList::CREATE_NOTE, 'medical_record.errors.not_allowed_create_note');

                $this->privateNotes->updateOrCreateForVisit(
                    $visit->id,
                    $visit->patient_id,
                    $actor->id,
                    [
                        'note' => $data['private_note']['note'],
                        'visibility' => 'private',
                    ]
                );
            }
        });

        return [
            'visit_session' => MedicalRecordVisitsHelper::formatVisitSession(
                $this->visits->findSession($visit->id, $actor->id),
                $actor
            ),
        ];
    }

    public function finalizeSession(int $visitId, Staff $actor): array
    {
        MedicalRecordVisitsHelper::authorize($actor, PermissionList::CREATE_VISIT_RECORD, 'medical_record.errors.not_allowed_finalize');

        $visit = $this->findEditableVisitOrFail($visitId, $actor);

        DB::transaction(function () use ($visit, $actor) {
            $finalizedAt = now();
            $visitAt = $visit->visit_at ?? $finalizedAt;

            $this->visits->updateVisit($visit, [
                'status' => VisitRecord::STATUS_FINALIZED,
                'finalized_at' => $finalizedAt,
                'visit_at' => $visitAt,
                'updated_by' => $actor->id,
            ]);

            $this->reports->finalizeForVisit($visit->id);
            $this->prescriptions->finalizeForVisit($visit->id);

            $this->records->updateLastVisit($visit->patient_id, [
                'last_visit_id' => $visit->id,
                'last_visit_at' => $visitAt,
                'summary' => $this->buildSummary($visit),
                'updated_by' => $actor->id,
            ]);

            $this->completeAppointmentIfNeeded($visit->appointment_id, $actor);
        });

        return [
            'visit_session' => MedicalRecordVisitsHelper::formatVisitSession(
                $this->visits->findSession($visit->id, $actor->id),
                $actor
            ),
        ];
    }

    private function findEditableVisitOrFail(int $visitId, Staff $actor): VisitRecord
    {
        $visit = $this->visits->findById($visitId);

        if (! $visit) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('medical_record.errors.visit_not_found')
            );
        }

        if ($visit->status === VisitRecord::STATUS_FINALIZED) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('medical_record.errors.visit_already_finalized')
            );
        }

        if ($visit->status === VisitRecord::STATUS_CANCELLED) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('medical_record.errors.visit_cancelled')
            );
        }

        if (
            $actor->hasRole(RoleEnum::DOCTOR->value, 'api')
            && $visit->doctor_id !== null
            && (int) $visit->doctor_id !== (int) $actor->id
        ) {
            throw new HttpException(
                Response::HTTP_FORBIDDEN,
                __('medical_record.errors.visit_doctor_mismatch')
            );
        }

        return $visit;
    }

    private function completeAppointmentIfNeeded(?int $appointmentId, Staff $actor): void
    {
        if (! $appointmentId) {
            return;
        }

        $appointment = $this->appointments->findAppointmentById($appointmentId);

        if (! $appointment || $appointment->status !== Appointment::STATUS_IN_PROGRESS) {
            return;
        }

        $payload = ['status' => Appointment::STATUS_COMPLETED];

        if (Schema::hasColumn('appointments', 'completed_at')) {
            $payload['completed_at'] = now();
        }

        if (Schema::hasColumn('appointments', 'completed_by')) {
            $payload['completed_by'] = $actor->id;
        }

        if (Schema::hasColumn('appointments', 'updated_by')) {
            $payload['updated_by'] = $actor->id;
        }

        $this->appointments->updateAppointment($appointment, $payload);
    }

    private function buildSummary(VisitRecord $visit): ?string
    {
        $fresh = $visit->fresh();

        $summary = $fresh->diagnosis ?: $fresh->chief_complaint;

        if (! $summary) {
            return $this->records->findByPatientId($visit->patient_id)?->summary;
        }

        return Str::limit(trim($summary), 1000, '');
    }

    private function findAppointmentOrFail(int $appointmentId): Appointment
    {
        $appointment = $this->appointments->findAppointmentById($appointmentId);

        if (! $appointment) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('medical_record.errors.appointment_not_found')
            );
        }

        return $appointment;
    }
}

<?php

namespace App\Modules\MedicalRecords\Services;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Appointments\Repositories\AppointmentRepository;
use App\Modules\Authentication\Models\Staff;
use App\Modules\MedicalRecords\Models\DoctorPrivateNote;
use App\Modules\MedicalRecords\Models\EyeMeasurement;
use App\Modules\MedicalRecords\Models\MedicalReport;
use App\Modules\MedicalRecords\Models\Prescription;
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
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        $this->authorize($actor, PermissionList::VIEW_MEDICAL_RECORDS, 'medical_record.errors.not_allowed_view_record');

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
            'visit_session' => $this->formatVisitSession($session, $actor),
        ];
    }

    public function openSession(int $appointmentId, array $data, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::CREATE_VISIT_RECORD, 'medical_record.errors.not_allowed_create_visit');

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
                'visit_session' => $this->formatVisitSession($session, $actor),
            ];
        }

        $this->ensureAppointmentReadyForVisit($appointment, $actor);

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
            'visit_session' => $this->formatVisitSession($session, $actor),
        ];
    }

    public function saveSession(int $visitId, array $data, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::CREATE_VISIT_RECORD, 'medical_record.errors.not_allowed_save_session');

        $visit = $this->findEditableVisitOrFail($visitId, $actor);

        $doctorId = $visit->doctor_id ?? $actor->id;

        DB::transaction(function () use ($visit, $data, $actor, $doctorId) {
            $visitPayload = $this->extractVisitFields($data['visit'] ?? []);
            $visitPayload['updated_by'] = $actor->id;
            $this->visits->updateVisit($visit, $visitPayload);

            if ($this->present($data, 'eye_measurement')) {
                $this->requirePermission($actor, PermissionList::CREATE_MEASUREMENT, 'medical_record.errors.not_allowed_create_measurement');

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

            if ($this->present($data, 'report')) {
                $this->requirePermission($actor, PermissionList::CREATE_REPORT, 'medical_record.errors.not_allowed_create_report');

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

            if ($this->present($data, 'prescription')) {
                $this->requirePermission($actor, PermissionList::CREATE_PRESCRIPTION, 'medical_record.errors.not_allowed_create_prescription');

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
                $this->requirePermission($actor, PermissionList::ADD_DISEASE_CLASSIFICATION, 'medical_record.errors.not_allowed_add_disease_classification');

                $this->diagnosisCodes->syncForVisit(
                    $visit->id,
                    $visit->patient_id,
                    $doctorId,
                    $actor->id,
                    $data['diagnosis_codes']
                );
            }

            if ($this->present($data, 'private_note') && ! empty($data['private_note']['note'])) {
                $this->requirePermission($actor, PermissionList::CREATE_NOTE, 'medical_record.errors.not_allowed_create_note');

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
            'visit_session' => $this->formatVisitSession(
                $this->visits->findSession($visit->id, $actor->id),
                $actor
            ),
        ];
    }

    public function finalizeSession(int $visitId, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::CREATE_VISIT_RECORD, 'medical_record.errors.not_allowed_finalize');

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
            'visit_session' => $this->formatVisitSession(
                $this->visits->findSession($visit->id, $actor->id),
                $actor
            ),
        ];
    }

    private function formatVisitSession(VisitRecord $visit, Staff $actor): array
    {
        return [
            'id' => $visit->id,
            'status' => $visit->status,
            'status_label' => Str::headline($visit->status),
            'is_finalized' => $visit->isFinalized(),
            'visit_type' => $visit->visit_type,
            'visit_at' => $this->formatDate($visit->visit_at),
            'display_date' => $this->formatDisplayDate($visit->visit_at),
            'finalized_at' => $this->formatDate($visit->finalized_at),
            'patient' => $this->formatPatientSummary($visit),
            'doctor' => $this->formatDoctorSummary($visit),
            'appointment' => $this->formatAppointmentSummary($visit),
            'sections' => [
                'visit' => $this->formatVisitSection($visit),
                'eye_measurement' => $this->formatEyeMeasurementSection($visit->latestEyeMeasurement),
                'report' => $this->formatReportSection($visit->latestMedicalReport),
                'prescription' => $this->formatPrescriptionSection($visit->latestPrescription),
                'diagnosis_codes' => $this->formatDiagnosisCodesSection($visit),
                'private_note' => $this->formatPrivateNoteSection($visit, $actor),
            ],
            'actions' => $this->formatActions($visit),
        ];
    }

    private function formatPatientSummary(VisitRecord $visit): ?array
    {
        $patient = $visit->patient;

        if (! $patient) {
            return null;
        }

        return [
            'id' => $patient->id,
            'medical_file_number' => $patient->medical_file_number,
            'full_name' => $patient->full_name,
            'gender' => $patient->gender,
            'birth_date' => $patient->birth_date?->format('Y-m-d'),
            'age' => $patient->birth_date?->age,
        ];
    }

    private function formatDoctorSummary(VisitRecord $visit): ?array
    {
        if (! $visit->doctor) {
            return null;
        }

        return [
            'id' => $visit->doctor->id,
            'name' => $visit->doctor->name,
        ];
    }

    private function formatAppointmentSummary(VisitRecord $visit): ?array
    {
        $appointment = $visit->appointment;

        if (! $appointment) {
            return null;
        }

        return [
            'id' => $appointment->id,
            'status' => $appointment->status,
            'type' => $appointment->appointment_type ?: $appointment->type,
            'appointment_at' => $this->formatDate($appointment->appointment_at),
            'reason' => $appointment->reason,
        ];
    }

    private function formatVisitSection(VisitRecord $visit): array
    {
        return [
            'visit_type' => $visit->visit_type,
            'chief_complaint' => $visit->chief_complaint,
            'symptoms' => $visit->symptoms,
            'examination_notes' => $visit->examination_notes,
            'diagnosis' => $visit->diagnosis,
            'treatment_plan' => $visit->treatment_plan,
            'notes' => $visit->notes,
        ];
    }

    private function formatEyeMeasurementSection(?EyeMeasurement $measurement): array
    {
        return [
            'id' => $measurement?->id,
            'mode' => $measurement ? 'update' : 'create',
            'measured_at' => $this->formatDate($measurement?->measured_at),
            'visual_acuity' => [
                'od' => $measurement?->visual_acuity_od,
                'os' => $measurement?->visual_acuity_os,
                'od_placeholder' => 'e.g. 20/25',
                'os_placeholder' => 'e.g. 20/20',
            ],
            'iop' => [
                'unit' => 'mmHg',
                'od' => $this->formatNumber($measurement?->iop_od),
                'os' => $this->formatNumber($measurement?->iop_os),
                'od_placeholder' => 'e.g. 16',
                'os_placeholder' => 'e.g. 15',
            ],
            'notes' => $measurement?->notes,
        ];
    }

    private function formatReportSection(?MedicalReport $report): array
    {
        return [
            'id' => $report?->id,
            'mode' => $report ? 'update' : 'create',
            'title' => $report?->title,
            'report_text' => $report?->report_text,
            'status' => $report?->status ?? MedicalReport::STATUS_DRAFT,
            'images_count' => $report?->images->count() ?? 0,
            'images' => $report
                ? $report->images->map(fn ($image) => [
                    'id' => $image->id,
                    'imaging_request_id' => $image->imaging_request_id,
                    'imaging_file_id' => $image->imaging_file_id,
                    'notes' => $image->notes,
                ])->values()->all()
                : [],
        ];
    }

    private function formatPrescriptionSection(?Prescription $prescription): array
    {
        return [
            'id' => $prescription?->id,
            'mode' => $prescription ? 'update' : 'create',
            'prescription_text' => $prescription?->prescription_text,
            'status' => $prescription?->status ?? Prescription::STATUS_DRAFT,
            'notes' => $prescription?->notes,
            'items' => $prescription
                ? $prescription->items->map(fn ($item) => [
                    'id' => $item->id,
                    'medicine_name' => $item->medicine_name,
                    'dosage' => $item->dosage,
                    'frequency' => $item->frequency,
                    'duration' => $item->duration,
                ])->values()->all()
                : [],
        ];
    }

    private function formatDiagnosisCodesSection(VisitRecord $visit): array
    {
        return $visit->diagnosisCodeLinks
            ->map(function ($link) {
                $code = $link->diagnosisCode;

                if (! $code) {
                    return null;
                }

                return [
                    'id' => $code->id,
                    'code' => $code->code,
                    'label' => $code->code,
                    'full_label' => trim($code->code . ' - ' . $code->name_en),
                    'name_en' => $code->name_en,
                    'name_ar' => $code->name_ar,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function formatPrivateNoteSection(VisitRecord $visit, Staff $actor): array
    {
        /** @var DoctorPrivateNote|null $note */
        $note = $visit->privateNotes
            ->first(fn (DoctorPrivateNote $privateNote) => (int) $privateNote->doctor_id === (int) $actor->id);

        return [
            'id' => $note?->id,
            'mode' => $note ? 'update' : 'create',
            'note' => $note?->note,
            'visibility' => 'private',
            'access_scope' => 'own_doctor_only',
        ];
    }

    private function formatActions(VisitRecord $visit): array
    {
        $isDraft = $visit->status === VisitRecord::STATUS_DRAFT;
        $isFinalized = $visit->isFinalized();

        return [
            'can_save' => $isDraft,
            'can_finalize' => $isDraft,
            'can_print' => $isFinalized,
            'can_export_pdf' => $isFinalized,
        ];
    }

    private function formatNumber($value): int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        $number = (float) $value;

        return fmod($number, 1.0) === 0.0
            ? (int) $number
            : $number;
    }

    private function formatDate($date): ?string
    {
        return $date?->toISOString();
    }

    private function formatDisplayDate($date): ?string
    {
        return $date?->format('M d, Y');
    }

    private function ensureAppointmentReadyForVisit(Appointment $appointment, Staff $actor): void
    {
        if ($appointment->status !== Appointment::STATUS_IN_PROGRESS) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('medical_record.errors.appointment_not_in_progress')
            );
        }

        if (! $appointment->doctor_id) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('medical_record.errors.appointment_no_doctor')
            );
        }

        if (
            $actor->hasRole(RoleEnum::DOCTOR->value, 'api')
            && (int) $appointment->doctor_id !== (int) $actor->id
        ) {
            throw new HttpException(
                Response::HTTP_FORBIDDEN,
                __('medical_record.errors.appointment_doctor_mismatch')
            );
        }
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

    private function extractVisitFields(array $visit): array
    {
        $allowed = [
            'visit_type',
            'chief_complaint',
            'symptoms',
            'examination_notes',
            'diagnosis',
            'treatment_plan',
            'notes',
        ];

        $payload = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $visit)) {
                $payload[$field] = $visit[$field];
            }
        }

        return $payload;
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

    private function present(array $data, string $key): bool
    {
        return array_key_exists($key, $data)
            && is_array($data[$key])
            && ! empty($data[$key]);
    }

    private function requirePermission(Staff $actor, string $permission, string $messageKey): void
    {
        if (! AccessControlHelper::staffHasPermission($actor, $permission)) {
            throw new HttpException(Response::HTTP_FORBIDDEN, __($messageKey));
        }
    }

    private function authorize(Staff $actor, string $permission, string $messageKey): void
    {
        if (! AccessControlHelper::staffHasPermission($actor, $permission)) {
            throw new HttpException(Response::HTTP_FORBIDDEN, __($messageKey));
        }
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

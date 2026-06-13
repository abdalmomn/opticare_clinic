<?php

namespace App\Modules\MedicalRecords\Helpers;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Authentication\Models\Staff;
use App\Modules\MedicalRecords\Models\DoctorPrivateNote;
use App\Modules\MedicalRecords\Models\EyeMeasurement;
use App\Modules\MedicalRecords\Models\MedicalReport;
use App\Modules\MedicalRecords\Models\Prescription;
use App\Modules\MedicalRecords\Models\VisitRecord;
use App\Modules\RolesPermissions\Enums\RoleEnum;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MedicalRecordVisitsHelper {

    public static function formatVisitSession(VisitRecord $visit, Staff $actor): array
    {
        return [
            'id' => $visit->id,
            'status' => $visit->status,
            'status_label' => Str::headline($visit->status),
            'is_finalized' => $visit->isFinalized(),
            'visit_type' => $visit->visit_type,
            'visit_at' => self::formatDate($visit->visit_at),
            'display_date' => self::formatDisplayDate($visit->visit_at),
            'finalized_at' => self::formatDate($visit->finalized_at),
            'patient' => self::formatPatientSummary($visit),
            'doctor' => self::formatDoctorSummary($visit),
            'appointment' => self::formatAppointmentSummary($visit),
            'sections' => [
                'visit' => self::formatVisitSection($visit),
                'eye_measurement' => self::formatEyeMeasurementSection($visit->latestEyeMeasurement),
                'report' => self::formatReportSection($visit->latestMedicalReport),
                'prescription' => self::formatPrescriptionSection($visit->latestPrescription),
                'diagnosis_codes' => self::formatDiagnosisCodesSection($visit),
                'private_note' => self::formatPrivateNoteSection($visit, $actor),
            ],
            'actions' => self::formatActions($visit),
        ];
    }

    public static function formatPatientSummary(VisitRecord $visit): ?array
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

    public static function formatDoctorSummary(VisitRecord $visit): ?array
    {
        if (! $visit->doctor) {
            return null;
        }

        return [
            'id' => $visit->doctor->id,
            'name' => $visit->doctor->name,
        ];
    }

    public static function formatAppointmentSummary(VisitRecord $visit): ?array
    {
        $appointment = $visit->appointment;

        if (! $appointment) {
            return null;
        }

        return [
            'id' => $appointment->id,
            'status' => $appointment->status,
            'type' => $appointment->appointment_type ?: $appointment->type,
            'appointment_at' => self::formatDate($appointment->appointment_at),
            'reason' => $appointment->reason,
        ];
    }

    public static function formatVisitSection(VisitRecord $visit): array
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

    public static function formatEyeMeasurementSection(?EyeMeasurement $measurement): array
    {
        return [
            'id' => $measurement?->id,
            'mode' => $measurement ? 'update' : 'create',
            'measured_at' => self::formatDate($measurement?->measured_at),
            'visual_acuity' => [
                'od' => $measurement?->visual_acuity_od,
                'os' => $measurement?->visual_acuity_os,
                'od_placeholder' => 'e.g. 20/25',
                'os_placeholder' => 'e.g. 20/20',
            ],
            'iop' => [
                'unit' => 'mmHg',
                'od' => self::formatNumber($measurement?->iop_od),
                'os' => self::formatNumber($measurement?->iop_os),
                'od_placeholder' => 'e.g. 16',
                'os_placeholder' => 'e.g. 15',
            ],
            'notes' => $measurement?->notes,
        ];
    }

    public static function formatReportSection(?MedicalReport $report): array
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

    public static function formatPrescriptionSection(?Prescription $prescription): array
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

    public static function formatDiagnosisCodesSection(VisitRecord $visit): array
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

    public static function formatPrivateNoteSection(VisitRecord $visit, Staff $actor): array
    {
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

    public static function formatActions(VisitRecord $visit): array
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

    public static function formatNumber($value): int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        $number = (float) $value;

        return fmod($number, 1.0) === 0.0
            ? (int) $number
            : $number;
    }

    public static function formatDate($date): ?string
    {
        return $date?->toISOString();
    }

    public static function formatDisplayDate($date): ?string
    {
        return $date?->format('M d, Y');
    }

    public static function ensureAppointmentReadyForVisit(Appointment $appointment, Staff $actor): void
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

    public static function extractVisitFields(array $visit): array
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

    public static function present(array $data, string $key): bool
    {
        return array_key_exists($key, $data)
            && is_array($data[$key])
            && ! empty($data[$key]);
    }

    public static function requirePermission(Staff $actor, string $permission, string $messageKey): void
    {
        if (! AccessControlHelper::staffHasPermission($actor, $permission)) {
            throw new HttpException(Response::HTTP_FORBIDDEN, __($messageKey));
        }
    }

    public static function authorize(Staff $actor, string $permission, string $messageKey): void
    {
        if (! AccessControlHelper::staffHasPermission($actor, $permission)) {
            throw new HttpException(Response::HTTP_FORBIDDEN, __($messageKey));
        }
    }
}

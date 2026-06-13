<?php
namespace App\Modules\MedicalRecords\Helpers;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Authentication\Models\Staff;
use App\Modules\MedicalRecords\Models\DoctorPrivateNote;
use App\Modules\MedicalRecords\Models\EyeMeasurement;
use App\Modules\MedicalRecords\Models\MedicalReport;
use App\Modules\MedicalRecords\Models\Prescription;
use App\Modules\MedicalRecords\Models\VisitRecord;
use App\Modules\Patients\Models\ClinicPatient;
use App\Modules\RolesPermissions\Enums\RoleEnum;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MedicalRecordHelper {

    public static function formatEyeMeasurement($measurement): ?array
    {
        if (! $measurement) {
            return null;
        }

        return [
            'id' => $measurement->id,
            'measured_at' => optional($measurement->measured_at)->toDateTimeString(),
            'visual_acuity' => [
                'od' => $measurement->visual_acuity_od,
                'os' => $measurement->visual_acuity_os,
            ],
            'iop' => [
                'od' => [
                    'value' => $measurement->iop_od,
                    'unit' => 'mmHg',
                ],
                'os' => [
                    'value' => $measurement->iop_os,
                    'unit' => 'mmHg',
                ],
            ],
        ];
    }

    public static function formatVitalSigns($vitals): ?array
    {
        if (! $vitals) {
            return null;
        }

        return [
            'recorded_at' => optional($vitals->recorded_at)->toDateTimeString(),
            'blood_pressure' => $vitals->blood_pressure,
            'heart_rate' => $vitals->heart_rate !== null ? [
                'value' => $vitals->heart_rate,
                'unit' => 'bpm',
            ] : null,
            'temperature' => $vitals->temperature !== null ? [
                'value' => $vitals->temperature,
                'unit' => '°C',
            ] : null,
            'weight' => $vitals->weight !== null ? [
                'value' => $vitals->weight,
                'unit' => 'kg',
            ] : null,
            'height' => $vitals->height !== null ? [
                'value' => $vitals->height,
                'unit' => 'cm',
            ] : null,
            'oxygen_saturation' => $vitals->oxygen_saturation !== null ? [
                'value' => $vitals->oxygen_saturation,
                'unit' => '%',
            ] : null,
            'notes' => $vitals->notes,
        ];
    }

    public static function ensurePatientAccessible(Staff $actor, int $patientId): void
    {
        if ($actor->hasRole(RoleEnum::MEDICAL_CENTER_ADMIN->value, 'api')) {
            return;
        }

        if ($actor->hasRole(RoleEnum::DOCTOR->value, 'api')) {
            if (! config('opticare.is_medical_center', false)) {
                return;
            }

            $owns = Appointment::query()
                ->where('patient_id', $patientId)
                ->where('doctor_id', $actor->id)
                ->exists();

            if (! $owns) {
                throw new HttpException(
                    Response::HTTP_FORBIDDEN,
                    __('medical_record.errors.not_allowed_view_record')
                );
            }
        }
    }

    public static function authorize(Staff $actor, string $permission, string $messageKey): void
    {
        if (! AccessControlHelper::staffHasPermission($actor, $permission)) {
            throw new HttpException(Response::HTTP_FORBIDDEN, __($messageKey));
        }
    }

    public static function formatPaginated($paginator): array
    {
        return [
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ];
    }

    public static function formatVisitTimelineItem(VisitRecord $visit): array
    {
        return [
            'id' => $visit->id,
            'timeline_type' => 'visit',
            'visit_type' => $visit->visit_type,
            'title' => self::formatVisitTitle($visit->visit_type),
            'date' => $visit->visit_at?->toISOString() ?? $visit->created_at?->toISOString(),
            'status' => $visit->status,
            'doctor' => $visit->doctor ? [
                'id' => $visit->doctor->id,
                'name' => $visit->doctor->name,
            ] : null,
            'appointment' => $visit->appointment ? [
                'id' => $visit->appointment->id,
                'type' => $visit->appointment->appointment_type ?? $visit->appointment->type,
                'status' => $visit->appointment->status,
            ] : null,
            'is_clickable' => true,
        ];
    }

    public static function formatPrivateNoteTimelineItem(DoctorPrivateNote $note): array
    {
        $date = $note->updated_at ?: $note->created_at;
        $preview = trim(preg_replace('/\s+/', ' ', strip_tags($note->note)));

        return [
            'id' => $note->id,
            'timeline_type' => 'private_note',
            'visit_id' => $note->visit_record_id,
            'date' => $date?->toISOString(),
            'display_date' => $date?->format('M d, Y'),
            'preview' => $preview !== '' ? Str::limit($preview, 120) : null,
            'visibility' => 'private',
            'access_scope' => 'own_doctor_only',
            'is_clickable' => true,
            'actions' => [
                'can_view' => true,
                'can_update' => true,
                'can_delete' => false,
            ],
        ];
    }

    public static function formatPrivateNoteDetails(DoctorPrivateNote $note): array
    {
        $date = $note->updated_at ?: $note->created_at;

        return [
            'id' => $note->id,
            'timeline_type' => 'private_note',
            'visit_id' => $note->visit_record_id,
            'date' => $date?->toISOString(),
            'display_date' => $date?->format('M d, Y'),
            'note' => $note->note,
            'visibility' => 'private',
            'access_scope' => 'own_doctor_only',
            'is_owner' => true,
        ];
    }

    public static function formatReportTimelineItem(MedicalReport $report): array
    {
        $date = $report->finalized_at ?: $report->created_at;
        $status = $report->status;

        $preview = $report->report_text
            ? trim(preg_replace('/\s+/', ' ', strip_tags($report->report_text)))
            : null;

        $isFinalized = $status === 'finalized';

        return [
            'id' => $report->id,
            'timeline_type' => 'report',
            'visit_id' => $report->visit_record_id,
            'title' => $report->title ?: 'Visit Report',
            'preview' => $preview ? Str::limit($preview, 120) : null,
            'status' => $status,
            'status_label' => Str::headline($status),
            'date' => $date?->toISOString(),
            'display_date' => $date?->format('M d, Y'),
            'doctor' => $report->doctor ? [
                'id' => $report->doctor->id,
                'name' => $report->doctor->name,
            ] : null,
            'images_count' => (int) ($report->images_count ?? 0),
            'actions' => [
                'can_view' => true,
                'can_download' => $isFinalized,
                'can_print' => $isFinalized,
                'can_export_pdf' => $isFinalized,
            ],
            'is_clickable' => true,
        ];
    }

    public static function formatVisitTitle(?string $visitType): string
    {
        return match ($visitType) {
            'consultation' => 'Consultation',
            'follow_up' => 'Follow-up',
            'emergency' => 'Emergency',
            'post_op' => 'Post-op',
            default => 'Visit',
        };
    }

    public static function formatMeasurementTimelineItem(EyeMeasurement $measurement): array
    {
        $date = $measurement->measured_at ?: $measurement->created_at;

        return [
            'id' => $measurement->id,
            'timeline_type' => 'measurement',
            'visit_id' => $measurement->visit_record_id,
            'appointment_id' => $measurement->appointment_id,
            'date' => self::formatDate($date),
            'display_date' => self::formatDisplayDate($date),
            'doctor' => $measurement->doctor ? [
                'id' => $measurement->doctor->id,
                'name' => $measurement->doctor->name,
            ] : null,
            'visual_acuity' => [
                'label' => 'Visual Acuity',
                'od' => $measurement->visual_acuity_od,
                'os' => $measurement->visual_acuity_os,
            ],
            'iop' => [
                'label' => 'IOP',
                'unit' => 'mmHg',
                'od' => self::formatNumber($measurement->iop_od),
                'os' => self::formatNumber($measurement->iop_os),
            ],
            'notes' => $measurement->notes,
            'is_clickable' => false,
        ];
    }

    public static function formatPagination(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'has_more' => $paginator->hasMorePages(),
        ];
    }

    public static function formatDate($date): ?string
    {
        if (! $date) {
            return null;
        }

        return Carbon::parse($date)->toJSON();
    }

    public static function formatDisplayDate($date): ?string
    {
        if (! $date) {
            return null;
        }

        return Carbon::parse($date)->format('M Y');
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

    public static function formatPrescriptionTimelineItem(Prescription $prescription): array
    {
        $date = $prescription->finalized_at ?: $prescription->created_at;
        $status = $prescription->status;
        $isFinalized = $status === 'finalized';

        $preview = $prescription->prescription_text
            ? trim(preg_replace('/\s+/', ' ', strip_tags($prescription->prescription_text)))
            : null;

        $firstItem = $prescription->items->first();

        $title = self::formatPrescriptionTitle($prescription, $preview);

        return [
            'id' => $prescription->id,
            'timeline_type' => 'prescription',
            'visit_id' => $prescription->visit_record_id,
            'title' => $title,
            'preview' => $preview ? Str::limit($preview, 120) : null,
            'status' => $status,
            'status_label' => Str::headline($status),
            'date' => $date?->toISOString(),
            'display_date' => $date?->format('M d, Y'),
            'doctor' => $prescription->doctor ? [
                'id' => $prescription->doctor->id,
                'name' => $prescription->doctor->name,
            ] : null,
            'items_count' => (int) ($prescription->items_count ?? $prescription->items->count()),
            'medicines' => $prescription->items
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->medicine_name,
                    'dosage' => $item->dosage,
                    'frequency' => $item->frequency,
                    'duration' => $item->duration,
                ])
                ->values(),
            'notes' => $prescription->notes,
            'actions' => [
                'can_view' => true,
                'can_download' => $isFinalized,
                'can_print' => $isFinalized,
                'can_export_pdf' => $isFinalized,
            ],
            'is_clickable' => true,
        ];
    }

    public static function formatPrescriptionTitle(Prescription $prescription, ?string $preview): string
    {
        $firstItem = $prescription->items->first();

        if ($firstItem) {
            $title = collect([
                $firstItem->medicine_name,
                $firstItem->dosage,
                $firstItem->frequency,
            ])->filter()->join(' ');

            $extraItemsCount = $prescription->items->count() - 1;

            return $extraItemsCount > 0
                ? $title . ' +' . $extraItemsCount . ' more'
                : $title;
        }

        return $preview
            ? Str::limit($preview, 60)
            : 'Prescription';
    }

    public static function formatDiagnosisTimelineItem(VisitRecord $visit): array
    {
        $date = $visit->visit_at ?: $visit->finalized_at ?: $visit->created_at;

        $diagnosisSummary = $visit->diagnosis
            ? trim(preg_replace('/\s+/', ' ', strip_tags($visit->diagnosis)))
            : null;

        $codes = $visit->diagnosisCodeLinks
            ->map(function ($link) {
                $diagnosisCode = $link->diagnosisCode;

                if (! $diagnosisCode) {
                    return null;
                }

                return [
                    'id' => $diagnosisCode->id,
                    'code' => $diagnosisCode->code,
                    'label' => $diagnosisCode->code,
                    'full_label' => trim($diagnosisCode->code . ' - ' . $diagnosisCode->name_en),
                    'name_en' => $diagnosisCode->name_en,
                    'name_ar' => $diagnosisCode->name_ar,
                ];
            })
            ->filter()
            ->values();

        return [
            'id' => $visit->id,
            'timeline_type' => 'diagnosis',
            'visit_id' => $visit->id,
            'visit_type' => $visit->visit_type,
            'status' => $visit->status,
            'status_label' => Str::headline($visit->status),
            'date' => $date?->toISOString(),
            'display_date' => $date?->format('M d, Y'),
            'doctor' => $visit->doctor ? [
                'id' => $visit->doctor->id,
                'name' => $visit->doctor->name,
            ] : null,
            'diagnosis_summary' => $diagnosisSummary ? Str::limit($diagnosisSummary, 160) : null,
            'codes_count' => $codes->count(),
            'codes' => $codes,
            'is_clickable' => true,
        ];
    }
}

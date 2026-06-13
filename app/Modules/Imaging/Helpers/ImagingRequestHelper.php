<?php

namespace App\Modules\Imaging\Helpers;

use App\Modules\Authentication\Models\Staff;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;
use App\Modules\Imaging\Models\ImagingFile;
use App\Modules\Imaging\Models\ImagingRequestItem;
use Illuminate\Support\Carbon;
use App\Modules\Imaging\Models\ImagingRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ImagingRequestHelper
{
    public static function requestId($request): int
    {
        return $request instanceof ImagingRequest
            ? (int) $request->id
            : (int) $request;
    }

    public static function resolveTechnician(?int $technicianId): ?int
    {
        if ($technicianId === null) {
            return null;
        }

        $staff = Staff::find($technicianId);

        if (! $staff) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('imaging.errors.technician_not_found')
            );
        }

        if (! AccessControlHelper::staffHasPermission($staff, PermissionList::VIEW_IMAGING_QUEUE)) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('imaging.errors.not_a_technician')
            );
        }

        return $staff->id;
    }

    public static function ensurePatientVisitAppointmentConsistency(
        int $patientId,
        ?\App\Modules\MedicalRecords\Models\VisitRecord $visitRecord,
        ?\App\Modules\Appointments\Models\Appointment $appointment
    ): void {
        if ($visitRecord && (int) $visitRecord->patient_id !== $patientId) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('imaging.errors.visit_patient_mismatch')
            );
        }

        if ($appointment && (int) $appointment->patient_id !== $patientId) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('imaging.errors.appointment_patient_mismatch')
            );
        }

        if ($visitRecord && $appointment && $visitRecord->appointment_id !== null && $visitRecord->appointment_id !== $appointment->id) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('imaging.errors.visit_appointment_conflict')
            );
        }
    }

    public static function resolveSource(Staff $actor, array $data): string
    {
        $hasSecretaryPermission = AccessControlHelper::staffHasPermission(
            $actor,
            PermissionList::CREATE_IMAGING_REQUEST_FOR_PATIENT
        );

        $hasDoctorPermission = AccessControlHelper::staffHasPermission(
            $actor,
            PermissionList::CREATE_IMAGING_REQUEST
        );

        if ($hasSecretaryPermission && ! $hasDoctorPermission) {
            return ImagingRequest::SOURCE_SECRETARY_REQUEST;
        }

        return ImagingRequest::SOURCE_DOCTOR_REQUEST;
    }

    public static function resolveRequestedBy(Staff $actor, ?int $requestedBy): ?int
    {
        $isSecretary = AccessControlHelper::staffHasPermission(
            $actor,
            PermissionList::CREATE_IMAGING_REQUEST_FOR_PATIENT
        ) && ! AccessControlHelper::staffHasPermission($actor, PermissionList::CREATE_IMAGING_REQUEST);

        if (! $isSecretary) {
            return $actor->id;
        }

        if ($requestedBy === null) {
            return null;
        }

        $staff = Staff::find($requestedBy);

        if (! $staff) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('imaging.errors.requested_by_not_found')
            );
        }

        return $staff->id;
    }

    public static function formatRequest(ImagingRequest $request, Staff $actor): array
    {
        $status = ImagingRequest::normalizeStatus($request->status ?? '');

        $formatted = [
            'id' => $request->id,
            'status' => $status,
            'status_label' => self::formatStatusLabel($status),
            'payment_status' => $request->payment_status ?? ImagingRequest::PAYMENT_STATUS_PENDING,
            'payment_status_label' => self::formatPaymentStatusLabel($request->payment_status ?? ImagingRequest::PAYMENT_STATUS_PENDING),
            'priority' => $request->priority,
            'request_type' => $request->request_type,
            'source' => $request->source,
            'patient' => self::formatPatientSummary($request->patient),
            'requested_by' => self::formatStaffSummary($request->requestedBy),
            'room' => self::formatRoomSummary($request->room),
            'visit_record_id' => $request->visit_record_id,
            'appointment_id' => $request->appointment_id,
            'requested_types' => self::formatRequestedTypes($request->items),
            'payment' => self::formatPayment($request),
            'technician' => self::formatStaffSummary($request->technician),
            'timestamps' => self::formatTimestamps($request),
            'notes' => $request->notes,
            'cancel_reason' => $request->cancel_reason,
        ];

        if ($request->relationLoaded('files')) {
            $formatted['files'] = $request->files
                ->map(fn (ImagingFile $file) => self::formatFile($file))
                ->all();
            $formatted['files_count'] = count($formatted['files']);
        }

        return $formatted;
    }

    public static function formatFile(ImagingFile $file): array
    {
        return [
            'id' => $file->id,
            'label' => self::formatFileLabel($file),
            'image_type' => $file->image_type !== null && $file->image_type !== ''
                ? $file->image_type
                : ($file->modality !== null && $file->modality !== '' ? $file->modality : null),
            'modality' => $file->modality,
            'eye' => $file->eye,
            'region' => $file->region,
            'file_name' => $file->file_name,
            'file_url' => self::formatFileUrl($file->file_path),
            'thumbnail_url' => $file->thumbnail_path
                ? self::formatFileUrl($file->thumbnail_path)
                : null,
            'file_size' => $file->file_size,
            'mime_type' => $file->mime_type,
            'captured_at' => self::formatDate($file->captured_at),
            'uploaded_at' => self::formatDate($file->uploaded_at),
            'source' => $file->source,
            'is_primary' => (bool) $file->is_primary,
            'imaging_request_item_id' => $file->imaging_request_item_id,
            'device' => self::formatDeviceSummary($file),
            'uploaded_by' => $file->relationLoaded('uploader')
                ? self::formatStaffSummary($file->uploader)
                : null,
        ];
    }

    public static function formatDeviceSummary(ImagingFile $file): ?array
    {
        if ($file->relationLoaded('device') && $file->device) {
            return [
                'id' => $file->device->id,
                'name' => $file->device->name,
                'device_identifier' => $file->device->device_identifier,
                'type' => $file->device->device_type,
            ];
        }

        if (! empty($file->device_name)) {
            return [
                'id' => $file->device_id,
                'name' => $file->device_name,
                'device_identifier' => null,
                'type' => null,
            ];
        }

        return null;
    }

    public static function formatFileLabel(ImagingFile $file): ?string
    {
        if (! empty($file->image_label)) {
            return $file->image_label;
        }

        if (! empty($file->region) && ! empty($file->eye)) {
            return $file->region.' '.$file->eye;
        }

        if (! empty($file->modality)) {
            return $file->modality;
        }

        return $file->file_name
            ? pathinfo($file->file_name, PATHINFO_FILENAME)
            : null;
    }

    public static function formatFileUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }

    public static function formatPatientSummary(?\App\Modules\Patients\Models\ClinicPatient $patient): ?array
    {
        if (! $patient) {
            return null;
        }

        return [
            'id' => $patient->id,
            'full_name' => $patient->full_name,
            'birth_date' => $patient->birth_date
                            ? Carbon::parse($patient->birth_date)->format('Y-m-d')
                            : null,
            'medical_file_number' => $patient->medical_file_number,
        ];
    }

    public static function formatStaffSummary(?Staff $staff): ?array
    {
        if (! $staff) {
            return null;
        }

        return [
            'id' => $staff->id,
            'name' => $staff->name,
        ];
    }

    public static function formatRoomSummary(?\App\Modules\Clinic\Models\Room $room): ?array
    {
        if (! $room) {
            return null;
        }

        return [
            'id' => $room->id,
            'name' => $room->name,
        ];
    }

    public static function formatRequestedTypes($items): array
    {
        return collect($items)
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'image_type' => $item->image_type,
                    'eye' => $item->eye,
                    'region' => $item->region,
                    'notes' => $item->notes,
                    'status' => $item->status,
                    'status_label' => self::formatItemStatusLabel($item->status),
                ];
            })
            ->all();
    }

    public static function formatPayment(ImagingRequest $request): array
    {
        return [
            'status' => $request->payment_status ?? ImagingRequest::PAYMENT_STATUS_PENDING,
            'status_label' => self::formatPaymentStatusLabel($request->payment_status ?? ImagingRequest::PAYMENT_STATUS_PENDING),
            'confirmed_at' => self::formatDate($request->payment_confirmed_at),
            'confirmed_by' => self::formatStaffSummary($request->confirmedBy),
        ];
    }

    public static function formatTimestamps(ImagingRequest $request): array
    {
        return [
            'created_at' => self::formatDate($request->created_at),
            'payment_confirmed_at' => self::formatDate($request->payment_confirmed_at),
            'sent_to_technician_at' => self::formatDate($request->sent_to_technician_at),
            'started_at' => self::formatDate($request->started_at),
            'completed_at' => self::formatDate($request->completed_at),
            'cancelled_at' => self::formatDate($request->cancelled_at),
        ];
    }

    public static function formatActions(ImagingRequest $request, Staff $actor): array
    {
        $status = ImagingRequest::normalizeStatus($request->status ?? '');

        return [
            'can_cancel' => Gate::forUser($actor)->allows('cancel', $request),
            'can_confirm_payment' => Gate::forUser($actor)->allows('confirmPayment', $request)
                && in_array($status, [ImagingRequest::STATUS_PENDING_PAYMENT, ImagingRequest::LEGACY_STATUS_PENDING], true),
            'can_send_to_technician' => Gate::forUser($actor)->allows('sendToTechnician', $request)
                && in_array($status, [ImagingRequest::STATUS_PAYMENT_CONFIRMED, ImagingRequest::STATUS_READY_FOR_IMAGING], true),
            'can_start' => Gate::forUser($actor)->allows('start', $request),
            'can_upload' => Gate::forUser($actor)->allows('uploadFiles', $request),
            'can_complete' => Gate::forUser($actor)->allows('complete', $request),
        ];
    }

    public static function formatPagination($paginator): array
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

    public static function formatDate($value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->toISOString();
    }

    public static function formatStatusLabel(string $status): string
    {
        return match ($status) {
            ImagingRequest::STATUS_REQUESTED => 'Requested',
            ImagingRequest::STATUS_PENDING_PAYMENT => 'Pending Payment',
            ImagingRequest::STATUS_PAYMENT_CONFIRMED => 'Payment Confirmed',
            ImagingRequest::STATUS_READY_FOR_IMAGING => 'Ready For Imaging',
            ImagingRequest::STATUS_IN_PROGRESS => 'In Progress',
            ImagingRequest::STATUS_COMPLETED => 'Completed',
            ImagingRequest::STATUS_CANCELLED => 'Cancelled',
            ImagingRequest::LEGACY_STATUS_PENDING => 'Pending Payment',
            ImagingRequest::LEGACY_STATUS_CANCELED => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public static function formatPaymentStatusLabel(string $status): string
    {
        return match ($status) {
            ImagingRequest::PAYMENT_STATUS_PENDING => 'Pending',
            ImagingRequest::PAYMENT_STATUS_CONFIRMED => 'Confirmed',
            ImagingRequest::PAYMENT_STATUS_WAIVED => 'Waived',
            ImagingRequest::PAYMENT_STATUS_REFUNDED => 'Refunded',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public static function formatItemStatusLabel(?string $status): string
    {
        return match ($status) {
            ImagingRequestItem::STATUS_REQUESTED => 'Requested',
            ImagingRequestItem::STATUS_CAPTURED => 'Captured',
            ImagingRequestItem::STATUS_SKIPPED => 'Skipped',
            default => ucfirst(str_replace('_', ' ', $status ?? 'requested')),
        };
    }

    public static function buildRequestTypeSummary(array $requestedTypes): string
    {
        $labels = array_map(function (array $requestedType) {
            $labelParts = [trim($requestedType['image_type'])];

            if (! empty($requestedType['region'])) {
                $labelParts[] = trim($requestedType['region']);
            }

            if (! empty($requestedType['eye'])) {
                $labelParts[] = trim($requestedType['eye']);
            }

            return trim(implode(' ', array_filter($labelParts)));
        }, $requestedTypes);

        return count($labels) === 1
            ? $labels[0]
            : implode(' + ', $labels);
    }
}

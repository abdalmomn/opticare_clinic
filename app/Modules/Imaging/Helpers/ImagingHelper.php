<?php

namespace App\Modules\Imaging\Helpers;

use App\Modules\Authentication\Models\Staff;
use App\Modules\Clinic\Models\ClinicDevice;
use App\Modules\Imaging\Models\ImagingActivityLog;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\Imaging\Models\ImagingRequestItem;
use App\Modules\Imaging\Services\ImagingFileService;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ImagingHelper{
    public static function attachItemsToMetadata(ImagingRequest $imagingRequest, array $metadata): array
    {
        $itemIdsByKey = [];

        foreach ($metadata as $index => $meta) {
            $key = implode('|', [
                $meta['image_type'],
                $meta['eye'] ?? '',
                $meta['region'] ?? '',
            ]);

            if (! isset($itemIdsByKey[$key])) {
                $item = $imagingRequest->items()->create([
                    'image_type' => $meta['image_type'],
                    'eye' => $meta['eye'] ?? null,
                    'region' => $meta['region'] ?? null,
                    'status' => ImagingRequestItem::STATUS_CAPTURED,
                ]);

                $itemIdsByKey[$key] = $item->id;
            }

            $metadata[$index]['imaging_request_item_id'] = $itemIdsByKey[$key];
        }

        return $metadata;
    }

    public static function buildRequestTypeSummary(array $metadata): string
    {
        $types = array_values(array_unique(array_map(
            fn (array $meta) => trim((string) $meta['image_type']),
            $metadata
        )));

        return implode(' + ', $types);
    }

    public static function ensureConsistency(
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

    public static function ensurePermission(Staff $actor, string $permission): void
    {
        if (! AccessControlHelper::staffHasPermission($actor, $permission)) {
            throw new HttpException(
                Response::HTTP_FORBIDDEN,
                __('imaging.errors.not_allowed_upload')
            );
        }
    }

    public static function cleanupStoredFiles(array $storedPaths): void
    {
        if ($storedPaths === []) {
            return;
        }

        Storage::disk(ImagingFileService::STORAGE_DISK)->delete($storedPaths);
    }

    public static function formatLog(ImagingActivityLog $log): array
    {
        return [
            'id' => $log->id,
            'action' => $log->action,
            'imaging_request_id' => $log->imaging_request_id,
            'imaging_file_id' => $log->imaging_file_id,
            'actor' => $log->actor
                ? ['id' => $log->actor->id, 'name' => $log->actor->name]
                : null,
            'from_status' => $log->from_status,
            'to_status' => $log->to_status,
            'metadata' => $log->metadata,
            'created_at' => $log->created_at
                ? Carbon::parse($log->created_at)->toISOString()
                : null,
        ];
    }

    public static function formatDevice(ClinicDevice $device): array
    {
        return [
            'id' => $device->id,
            'name' => $device->name,
            'device_identifier' => $device->device_identifier,
            'serial_number' => $device->serial_number,
            'device_type' => $device->device_type,
            'manufacturer' => $device->manufacturer,
            'model' => $device->model,
            'status' => $device->status,
            'room' => $device->room
                ? ['id' => $device->room->id, 'name' => $device->room->name]
                : null,
            'last_maintenance_at' => $device->last_maintenance_at
                ? Carbon::parse($device->last_maintenance_at)->format('Y-m-d')
                : null,
            'notes' => $device->notes,
            'created_by' => $device->createdBy
                ? ['id' => $device->createdBy->id, 'name' => $device->createdBy->name]
                : null,
            'updated_by' => $device->updatedBy
                ? ['id' => $device->updatedBy->id, 'name' => $device->updatedBy->name]
                : null,
            'created_at' => $device->created_at?->toISOString(),
            'updated_at' => $device->updated_at?->toISOString(),
        ];
    }

    public static function resolveRequestItem(ImagingRequest $imagingRequest, $itemId): ?ImagingRequestItem
    {
        if (empty($itemId)) {
            return null;
        }

        $item = ImagingRequestItem::query()
            ->whereKey((int) $itemId)
            ->where('imaging_request_id', $imagingRequest->id)
            ->first();

        if (! $item) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('imaging.errors.item_mismatch')
            );
        }

        return $item;
    }

    public static function normalizeStatusCounts($counts): array
    {
        $normalized = [];

        foreach ($counts as $status => $count) {
            $key = ImagingRequest::normalizeStatus((string) $status);
            $normalized[$key] = ($normalized[$key] ?? 0) + (int) $count;
        }

        return $normalized;
    }

    public static function ensureCanViewStatistics(Staff $actor): void
    {
        if (! AccessControlHelper::staffHasPermission($actor, PermissionList::VIEW_STATISTICS)) {
            throw new HttpException(
                Response::HTTP_FORBIDDEN,
                __('imaging.errors.not_allowed_statistics')
            );
        }
    }
}

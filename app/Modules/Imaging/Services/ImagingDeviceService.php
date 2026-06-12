<?php

namespace App\Modules\Imaging\Services;

use App\Modules\Authentication\Models\Staff;
use App\Modules\Clinic\Models\ClinicDevice;
use App\Modules\Imaging\Models\ImagingActivityLog;
use App\Modules\Imaging\Repositories\ImagingDeviceRepository;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ImagingDeviceService
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_MAINTENANCE = 'maintenance';

    public const STATUS_OFFLINE = 'offline';

    public const STATUS_RETIRED = 'retired';

    public function __construct(
        protected ImagingDeviceRepository $repository,
        protected ImagingActivityLogService $activityLog
    ) {}

    public function list(array $filters, Staff $actor): array
    {
        $this->ensurePermission($actor, PermissionList::VIEW_DEVICES, 'not_allowed_view_devices');

        $paginator = $this->repository->paginateWithFilters($filters);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (ClinicDevice $device) => $this->formatDevice($device))
                ->all(),
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

    public function show($device, Staff $actor): array
    {
        $this->ensurePermission($actor, PermissionList::VIEW_DEVICES, 'not_allowed_view_devices');

        return ['device' => $this->formatDevice($this->resolveDevice($device))];
    }

    public function create(array $data, Staff $actor): array
    {
        $this->ensurePermission($actor, PermissionList::CREATE_DEVICE, 'not_allowed_manage_devices');

        $device = $this->repository->create([
            'name' => $data['name'],
            'device_identifier' => $data['device_identifier'] ?? null,
            'serial_number' => $data['serial_number'] ?? null,
            'device_type' => $data['device_type'],
            'manufacturer' => $data['manufacturer'] ?? null,
            'model' => $data['model'] ?? null,
            'room_id' => $data['room_id'] ?? null,
            'status' => $data['status'] ?? self::STATUS_ACTIVE,
            'last_maintenance_at' => $data['last_maintenance_at'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $this->activityLog->record(
            ImagingActivityLog::ACTION_DEVICE_CREATED,
            actorId: $actor->id,
            metadata: ['device_id' => $device->id, 'name' => $device->name]
        );

        return ['device' => $this->formatDevice($this->repository->findWithRelations($device->id))];
    }

    public function update($device, array $data, Staff $actor): array
    {
        $this->ensurePermission($actor, PermissionList::EDIT_DEVICE, 'not_allowed_manage_devices');

        $clinicDevice = $this->resolveDevice($device);

        $payload = collect($data)
            ->only([
                'name',
                'device_identifier',
                'serial_number',
                'device_type',
                'manufacturer',
                'model',
                'room_id',
                'status',
                'last_maintenance_at',
                'notes',
            ])
            ->all();

        $payload['updated_by'] = $actor->id;

        $clinicDevice->update($payload);

        $this->activityLog->record(
            ImagingActivityLog::ACTION_DEVICE_UPDATED,
            actorId: $actor->id,
            metadata: ['device_id' => $clinicDevice->id, 'name' => $clinicDevice->name]
        );

        return ['device' => $this->formatDevice($this->repository->findWithRelations($clinicDevice->id))];
    }

    public function toggleStatus($device, Staff $actor): array
    {
        $this->ensurePermission($actor, PermissionList::TOGGLE_DEVICE_STATUS, 'not_allowed_manage_devices');

        $clinicDevice = $this->resolveDevice($device);

        $previousStatus = $clinicDevice->status;

        if ($previousStatus === self::STATUS_RETIRED) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('imaging.errors.cannot_toggle_retired_device')
            );
        }

        $newStatus = $previousStatus === self::STATUS_ACTIVE
            ? self::STATUS_OFFLINE
            : self::STATUS_ACTIVE;

        $clinicDevice->update([
            'status' => $newStatus,
            'updated_by' => $actor->id,
        ]);

        $this->activityLog->record(
            $newStatus === self::STATUS_ACTIVE
                ? ImagingActivityLog::ACTION_DEVICE_ACTIVATED
                : ImagingActivityLog::ACTION_DEVICE_DEACTIVATED,
            actorId: $actor->id,
            metadata: [
                'device_id' => $clinicDevice->id,
                'name' => $clinicDevice->name,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
            ]
        );

        return [
            'device' => $this->formatDevice(
                $this->repository->findWithRelations($clinicDevice->id)
            ),
        ];
    }

    public function delete($device, Staff $actor): array
    {
        $this->ensurePermission($actor, PermissionList::DELETE_DEVICE, 'not_allowed_manage_devices');

        $clinicDevice = $this->resolveDevice($device);

        if ($this->repository->isReferencedByImagingFiles($clinicDevice->id)) {
            $clinicDevice->update([
                'status' => self::STATUS_RETIRED,
                'updated_by' => $actor->id,
            ]);

            $this->activityLog->record(
                ImagingActivityLog::ACTION_DEVICE_DELETED_OR_RETIRED,
                actorId: $actor->id,
                metadata: ['device_id' => $clinicDevice->id, 'name' => $clinicDevice->name, 'retired_instead' => true]
            );

            return [
                'deleted' => false,
                'retired_instead' => true,
                'device' => $this->formatDevice($this->repository->findWithRelations($clinicDevice->id)),
            ];
        }

        $deviceId = $clinicDevice->id;
        $deviceName = $clinicDevice->name;

        $clinicDevice->delete();

        $this->activityLog->record(
            ImagingActivityLog::ACTION_DEVICE_DELETED_OR_RETIRED,
            actorId: $actor->id,
            metadata: ['device_id' => $deviceId, 'name' => $deviceName, 'retired_instead' => false]
        );

        return [
            'deleted' => true,
            'retired_instead' => false,
            'device' => null,
        ];
    }

    private function resolveDevice($device): ClinicDevice
    {
        $clinicDevice = $this->repository->findWithRelations(
            $device instanceof ClinicDevice ? (int) $device->id : (int) $device
        );

        if (! $clinicDevice) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('imaging.errors.device_not_found')
            );
        }

        return $clinicDevice;
    }

    private function ensurePermission(Staff $actor, string $permission, string $errorKey): void
    {
        if (! AccessControlHelper::staffHasPermission($actor, $permission)) {
            throw new HttpException(
                Response::HTTP_FORBIDDEN,
                __('imaging.errors.'.$errorKey)
            );
        }
    }

    private function formatDevice(ClinicDevice $device): array
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
}

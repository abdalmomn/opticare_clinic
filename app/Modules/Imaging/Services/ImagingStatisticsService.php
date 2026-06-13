<?php

namespace App\Modules\Imaging\Services;

use App\Modules\Authentication\Models\Staff;
use App\Modules\Imaging\Helpers\ImagingHelper;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\Imaging\Repositories\ImagingStatisticsRepository;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ImagingStatisticsService
{
    public function __construct(
        protected ImagingStatisticsRepository $repository
    ) {}

    public function overview(array $filters, Staff $actor): array
    {
        ImagingHelper::ensureCanViewStatistics($actor);

        $byStatus = ImagingHelper::normalizeStatusCounts($this->repository->countByStatus($filters));

        return [
            'totals' => [
                'requests' => $this->repository->countRequests($filters),
                'completed' => $byStatus[ImagingRequest::STATUS_COMPLETED] ?? 0,
                'cancelled' => $byStatus[ImagingRequest::STATUS_CANCELLED] ?? 0,
                'pending_payment' => $byStatus[ImagingRequest::STATUS_PENDING_PAYMENT] ?? 0,
                'payment_confirmed' => $byStatus[ImagingRequest::STATUS_PAYMENT_CONFIRMED] ?? 0,
                'ready_for_imaging' => $byStatus[ImagingRequest::STATUS_READY_FOR_IMAGING] ?? 0,
                'in_progress' => $byStatus[ImagingRequest::STATUS_IN_PROGRESS] ?? 0,
                'files' => $this->repository->countFiles($filters),
            ],
            'by_status' => collect($byStatus)
                ->map(fn (int $count, string $status) => [
                    'status' => $status,
                    'count' => $count,
                ])
                ->values()
                ->all(),
            'by_source' => $this->repository->countBySource($filters)
                ->map(fn ($count, $source) => [
                    'source' => $source,
                    'count' => (int) $count,
                ])
                ->values()
                ->all(),
            'by_priority' => $this->repository->countByPriority($filters)
                ->map(fn ($count, $priority) => [
                    'priority' => $priority,
                    'count' => (int) $count,
                ])
                ->values()
                ->all(),
            'by_day' => $this->repository->countByDay($filters)
                ->map(fn ($count, $day) => [
                    'day' => (string) $day,
                    'count' => (int) $count,
                ])
                ->values()
                ->all(),
        ];
    }

    public function byDevice(array $filters, Staff $actor): array
    {
        ImagingHelper::ensureCanViewStatistics($actor);

        $rows = $this->repository->aggregateByDevice($filters);
        $devices = $this->repository->devicesByIds($rows->pluck('device_id')->all());

        return [
            'items' => $rows
                ->map(function ($row) use ($devices) {
                    $device = $devices->get($row->device_id);

                    return [
                        'device' => $device ? [
                            'id' => $device->id,
                            'name' => $device->name,
                            'device_identifier' => $device->device_identifier,
                            'type' => $device->device_type,
                        ] : ['id' => $row->device_id, 'name' => null, 'device_identifier' => null, 'type' => null],
                        'files_count' => (int) $row->files_count,
                        'requests_count' => (int) $row->requests_count,
                        'last_upload_at' => $row->last_upload_at
                            ? Carbon::parse($row->last_upload_at)->toISOString()
                            : null,
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    public function byType(array $filters, Staff $actor): array
    {
        ImagingHelper::ensureCanViewStatistics($actor);

        return [
            'items' => $this->repository->aggregateByType($filters)
                ->map(fn ($row) => [
                    'image_type' => $row->resolved_type,
                    'files_count' => (int) $row->files_count,
                    'requests_count' => (int) $row->requests_count,
                ])
                ->values()
                ->all(),
        ];
    }
}

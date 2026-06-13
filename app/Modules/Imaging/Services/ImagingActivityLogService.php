<?php

namespace App\Modules\Imaging\Services;

use App\Modules\Authentication\Models\Staff;
use App\Modules\Imaging\Helpers\ImagingHelper;
use App\Modules\Imaging\Models\ImagingActivityLog;
use App\Modules\Imaging\Repositories\ImagingActivityLogRepository;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ImagingActivityLogService
{
    public function __construct(
        protected ImagingActivityLogRepository $repository
    ) {}

    public function list(array $filters, Staff $actor): array
    {
        if (! AccessControlHelper::staffHasPermission($actor, PermissionList::VIEW_ACTIVITY_LOG)) {
            throw new HttpException(
                Response::HTTP_FORBIDDEN,
                __('imaging.errors.not_allowed_activity_log')
            );
        }

        $paginator = $this->repository->paginateWithFilters($filters);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (ImagingActivityLog $log) => ImagingHelper::formatLog($log))
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

    public function record(
        string $action,
        ?int $imagingRequestId = null,
        ?int $imagingFileId = null,
        ?int $actorId = null,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?array $metadata = null
    ): void {
        try {
            $this->repository->create([
                'imaging_request_id' => $imagingRequestId,
                'imaging_file_id' => $imagingFileId,
                'actor_id' => $actorId,
                'action' => $action,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'metadata' => $metadata,
                'created_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Imaging activity log failed: '.$exception->getMessage(), [
                'action' => $action,
                'imaging_request_id' => $imagingRequestId,
                'imaging_file_id' => $imagingFileId,
            ]);
        }
    }
}

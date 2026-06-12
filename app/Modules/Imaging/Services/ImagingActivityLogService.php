<?php

namespace App\Modules\Imaging\Services;

use App\Modules\Authentication\Models\Staff;
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
                ->map(fn (ImagingActivityLog $log) => $this->formatLog($log))
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

    /**
     * Best-effort audit logging: a logging failure must never roll back or
     * abort the business transition that triggered it.
     */
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

    private function formatLog(ImagingActivityLog $log): array
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
}

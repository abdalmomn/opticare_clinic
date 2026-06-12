<?php

namespace App\Modules\Imaging\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\Imaging\Models\ImagingActivityLog;
use Illuminate\Pagination\LengthAwarePaginator;

class ImagingActivityLogRepository extends BaseRepository
{
    public function __construct(ImagingActivityLog $model)
    {
        parent::__construct($model);
    }

    public function paginateWithFilters(array $filters): LengthAwarePaginator
    {
        $query = $this->query()
            ->with([
                'actor:id,name',
            ]);

        if (! empty($filters['imaging_request_id'])) {
            $query->where('imaging_request_id', $filters['imaging_request_id']);
        }

        if (! empty($filters['imaging_file_id'])) {
            $query->where('imaging_file_id', $filters['imaging_file_id']);
        }

        if (! empty($filters['actor_id'])) {
            $query->where('actor_id', $filters['actor_id']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $perPage = isset($filters['per_page'])
            ? min(max((int) $filters['per_page'], 1), 100)
            : 15;

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}

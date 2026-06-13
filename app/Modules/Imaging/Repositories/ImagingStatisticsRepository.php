<?php

namespace App\Modules\Imaging\Repositories;

use App\Modules\Clinic\Models\ClinicDevice;
use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\Imaging\Models\ImagingFile;
use App\Modules\Imaging\Models\ImagingRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ImagingStatisticsRepository extends BaseRepository
{
    public function __construct(ImagingRequest $model)
    {
        parent::__construct($model);
    }

    public function countByStatus(array $filters): Collection
    {
        return $this->filteredRequests($filters)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
    }

    public function countBySource(array $filters): Collection
    {
        return $this->filteredRequests($filters)
            ->selectRaw('source, COUNT(*) as total')
            ->groupBy('source')
            ->pluck('total', 'source');
    }

    public function countByPriority(array $filters): Collection
    {
        return $this->filteredRequests($filters)
            ->selectRaw('priority, COUNT(*) as total')
            ->groupBy('priority')
            ->pluck('total', 'priority');
    }

    public function countByDay(array $filters): Collection
    {
        return $this->filteredRequests($filters)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');
    }

    public function countRequests(array $filters): int
    {
        return $this->filteredRequests($filters)->count();
    }

    public function countFiles(array $filters): int
    {
        return $this->filteredFiles($filters)->count();
    }

    public function aggregateByDevice(array $filters): Collection
    {
        return $this->filteredFiles($filters)
            ->selectRaw('device_id, COUNT(*) as files_count, COUNT(DISTINCT imaging_request_id) as requests_count, MAX(uploaded_at) as last_upload_at')
            ->whereNotNull('device_id')
            ->groupBy('device_id')
            ->orderByDesc('files_count')
            ->get();
    }

    public function aggregateByType(array $filters): Collection
    {
        return $this->filteredFiles($filters)
            ->selectRaw("COALESCE(NULLIF(image_type, ''), modality) as resolved_type, COUNT(*) as files_count, COUNT(DISTINCT imaging_request_id) as requests_count")
            ->whereRaw("COALESCE(NULLIF(image_type, ''), modality) IS NOT NULL")
            ->groupBy('resolved_type')
            ->orderByDesc('files_count')
            ->get();
    }

    public function devicesByIds(array $ids): Collection
    {
        return ClinicDevice::query()
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'device_identifier', 'device_type'])
            ->keyBy('id');
    }

    private function filteredRequests(array $filters): Builder
    {
        $query = $this->query();

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['doctor_id'])) {
            $query->where('requested_by', $filters['doctor_id']);
        }

        if (! empty($filters['technician_id'])) {
            $query->where('technician_id', $filters['technician_id']);
        }

        if (! empty($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (! empty($filters['device_id'])) {
            $query->whereHas('files', function (Builder $fileQuery) use ($filters) {
                $fileQuery->where('device_id', $filters['device_id']);
            });
        }

        return $query;
    }

    private function filteredFiles(array $filters): Builder
    {
        $query = ImagingFile::query();

        if (! empty($filters['date_from'])) {
            $query->whereDate('uploaded_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('uploaded_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['device_id'])) {
            $query->where('device_id', $filters['device_id']);
        }

        if (! empty($filters['source'])
            || ! empty($filters['doctor_id'])
            || ! empty($filters['technician_id'])
            || ! empty($filters['room_id'])
        ) {
            $query->whereHas('request', function (Builder $requestQuery) use ($filters) {
                if (! empty($filters['source'])) {
                    $requestQuery->where('source', $filters['source']);
                }

                if (! empty($filters['doctor_id'])) {
                    $requestQuery->where('requested_by', $filters['doctor_id']);
                }

                if (! empty($filters['technician_id'])) {
                    $requestQuery->where('technician_id', $filters['technician_id']);
                }

                if (! empty($filters['room_id'])) {
                    $requestQuery->where('room_id', $filters['room_id']);
                }
            });
        }

        return $query;
    }
}

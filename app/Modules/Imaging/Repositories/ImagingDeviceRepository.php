<?php

namespace App\Modules\Imaging\Repositories;

use App\Modules\Clinic\Models\ClinicDevice;
use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\Imaging\Models\ImagingFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ImagingDeviceRepository extends BaseRepository
{
    public function __construct(ClinicDevice $model)
    {
        parent::__construct($model);
    }

    public function findWithRelations(int $id): ?ClinicDevice
    {
        return $this->query()
            ->with([
                'room:id,name',
                'createdBy:id,name',
                'updatedBy:id,name',
            ])
            ->find($id);
    }

    public function paginateWithFilters(array $filters): LengthAwarePaginator
    {
        $query = $this->query()
            ->with([
                'room:id,name',
                'createdBy:id,name',
                'updatedBy:id,name',
            ]);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (! empty($filters['device_type'])) {
            $query->where('device_type', $filters['device_type']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);

            $query->where(function (Builder $subQuery) use ($search) {
                $subQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('device_identifier', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('manufacturer', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        }

        $perPage = isset($filters['per_page'])
            ? min(max((int) $filters['per_page'], 1), 100)
            : 15;

        return $query
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function isReferencedByImagingFiles(int $deviceId): bool
    {
        return ImagingFile::withTrashed()
            ->where('device_id', $deviceId)
            ->exists();
    }
}

<?php

namespace App\Modules\Imaging\Repositories;

use App\Modules\Authentication\Models\Staff;
use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\Imaging\Models\ImagingRequestItem;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ImagingRequestRepository extends BaseRepository
{
    public function __construct(ImagingRequest $model)
    {
        parent::__construct($model);
    }

    public function createWithItems(array $requestData, array $items): ImagingRequest
    {
        return DB::transaction(function () use ($requestData, $items) {
            $imagingRequest = $this->create($requestData);

            $payloadItems = array_map(function (array $item) use ($imagingRequest) {
                return [
                    'imaging_request_id' => $imagingRequest->id,
                    'image_type' => $item['image_type'],
                    'eye' => $item['eye'] ?? null,
                    'region' => $item['region'] ?? null,
                    'notes' => $item['notes'] ?? null,
                    'status' => ImagingRequestItem::STATUS_REQUESTED,
                ];
            }, $items);

            $imagingRequest->items()->createMany($payloadItems);

            return $this->findDetailed($imagingRequest->id);
        });
    }

    public function lockForUpdate(int $id): ?ImagingRequest
    {
        return $this->query()
            ->whereKey($id)
            ->lockForUpdate()
            ->first();
    }

    public function findDetailed(int $id): ?ImagingRequest
    {
        return $this->query()
            ->with([
                'patient:id,medical_file_number,full_name,birth_date',
                'requestedBy:id,name',
                'confirmedBy:id,name',
                'sentToTechnicianBy:id,name',
                'technician:id,name',
                'room:id,name',
                'items',
            ])
            ->find($id);
    }

    public function paginateForActor(Staff $actor, array $filters): LengthAwarePaginator
    {
        $query = $this->query()
                ->with([
                    'patient:id,medical_file_number,full_name,birth_date',
                    'requestedBy:id,name',
                    'technician:id,name',
                    'room:id,name',
                    'items',
                ]);

        if (! AccessControlHelper::staffHasPermission($actor, PermissionList::VIEW_ALL_IMAGING_REQUESTS)
            && ! AccessControlHelper::staffHasPermission($actor, PermissionList::VIEW_IMAGING_REQUESTS)
        ) {
            if (AccessControlHelper::staffHasPermission($actor, PermissionList::VIEW_OWN_IMAGING_REQUESTS)) {
                $query->where('requested_by', $actor->id);
            } elseif (AccessControlHelper::staffHasPermission($actor, PermissionList::VIEW_IMAGING_QUEUE)) {
                $query->whereIn('status', [
                    ImagingRequest::STATUS_PAYMENT_CONFIRMED,
                    ImagingRequest::STATUS_READY_FOR_IMAGING,
                    ImagingRequest::STATUS_IN_PROGRESS,
                ]);
            }
        }

        $this->applyFilters($query, $filters);

        $perPage = isset($filters['per_page'])
            ? min(max((int) $filters['per_page'], 1), 100)
            : 15;

        return $query
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function cancel(ImagingRequest $request, array $data): ImagingRequest
    {
        return DB::transaction(function () use ($request, $data) {
            $request->update($data);

            if ($request->queue()->exists()) {
                $request->queue()->update(['status' => 'cancelled']);
            }

            return $this->findDetailed($request->id);
        });
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }

        if (! empty($filters['requested_by'])) {
            $query->where('requested_by', $filters['requested_by']);
        }

        if (! empty($filters['technician_id'])) {
            $query->where('technician_id', $filters['technician_id']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['status'])) {
            $status = ImagingRequest::normalizeStatus($filters['status']);
            $query->where('status', $status);
        }

        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);

            $query->where(function (Builder $subQuery) use ($search) {
                $subQuery->where('request_type', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('patient', function (Builder $patientQuery) use ($search) {
                        $patientQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('medical_file_number', 'like', "%{$search}%");
                    });
            });
        }
    }
}

<?php

namespace App\Modules\MedicalRecords\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\MedicalRecords\Models\Prescription;
use Illuminate\Pagination\LengthAwarePaginator;

class PrescriptionRepository extends BaseRepository
{
    public function __construct(Prescription $model)
    {
        parent::__construct($model);
    }

    public function updateOrCreateForVisit(int $visitId, array $data): Prescription
    {
        return $this->model->newQuery()->updateOrCreate(
            ['visit_record_id' => $visitId],
            $data
        );
    }

    public function replaceItems(Prescription $prescription, array $items): void
    {
        $prescription->items()->delete();

        if (empty($items)) {
            return;
        }

        $prescription->items()->createMany($items);
    }

    public function finalizeForVisit(int $visitId): void
    {
        $this->model->newQuery()
            ->where('visit_record_id', $visitId)
            ->where('status', Prescription::STATUS_DRAFT)
            ->update([
                'status' => Prescription::STATUS_FINALIZED,
                'finalized_at' => now(),
            ]);
    }

    public function timeline(int $patientId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->select([
                'id',
                'patient_id',
                'visit_record_id',
                'doctor_id',
                'prescription_text',
                'status',
                'finalized_at',
                'notes',
                'created_at',
            ])
            ->with([
                'doctor:id,name',
                'items:id,prescription_id,medicine_name,dosage,frequency,duration',
            ])
            ->withCount('items')
            ->where('patient_id', $patientId);

        $status = $filters['status'] ?? 'finalized';

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if (! empty($filters['date_from'])) {
            $query->whereRaw(
                'DATE(COALESCE(finalized_at, created_at)) >= ?',
                [$filters['date_from']]
            );
        }

        if (! empty($filters['date_to'])) {
            $query->whereRaw(
                'DATE(COALESCE(finalized_at, created_at)) <= ?',
                [$filters['date_to']]
            );
        }

        $perPage = isset($filters['per_page'])
            ? min(max((int) $filters['per_page'], 1), 100)
            : 15;

        return $query
            ->orderByRaw('COALESCE(finalized_at, created_at) DESC')
            ->paginate($perPage);
    }
    }

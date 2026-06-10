<?php

namespace App\Modules\MedicalRecords\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\MedicalRecords\Models\EyeMeasurement;
use Illuminate\Pagination\LengthAwarePaginator;

class EyeMeasurementRepository extends BaseRepository
{
    public function __construct(EyeMeasurement $model)
    {
        parent::__construct($model);
    }

    public function updateOrCreateForVisit(int $visitId, array $data): EyeMeasurement
    {
        return $this->model->newQuery()->updateOrCreate(
            ['visit_record_id' => $visitId],
            $data
        );
    }

    public function latestForPatient(int $patientId): ?EyeMeasurement
    {
        return $this->model->newQuery()
            ->where('patient_id', $patientId)
            ->orderByRaw('COALESCE(measured_at, created_at) DESC')
            ->first();
    }

    public function timeline(int $patientId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->select([
                'id',
                'patient_id',
                'visit_record_id',
                'appointment_id',
                'doctor_id',
                'visual_acuity_od',
                'visual_acuity_os',
                'iop_od',
                'iop_os',
                'notes',
                'measured_at',
                'created_at',
            ])
            ->with([
                'doctor:id,name',
            ])
            ->where('patient_id', $patientId);

        if (! empty($filters['date_from'])) {
            $query->whereDate('measured_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('measured_at', '<=', $filters['date_to']);
        }

        $perPage = isset($filters['per_page'])
            ? min(max((int) $filters['per_page'], 1), 100)
            : 15;

        return $query
            ->orderByRaw('COALESCE(measured_at, created_at) DESC')
            ->paginate($perPage);
    }
}

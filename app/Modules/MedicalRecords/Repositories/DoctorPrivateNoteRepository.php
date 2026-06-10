<?php

namespace App\Modules\MedicalRecords\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\MedicalRecords\Models\DoctorPrivateNote;
use Illuminate\Pagination\LengthAwarePaginator;

class DoctorPrivateNoteRepository extends BaseRepository
{
    public function __construct(DoctorPrivateNote $model)
    {
        parent::__construct($model);
    }

    public function updateOrCreateForVisit(
        int $visitId,
        int $patientId,
        int $doctorId,
        array $data
    ): DoctorPrivateNote {
        return $this->model->newQuery()->updateOrCreate(
            [
                'visit_record_id' => $visitId,
                'doctor_id' => $doctorId,
            ],
            array_merge($data, ['patient_id' => $patientId])
        );
    }

    public function timelineForDoctor(int $patientId, int $doctorId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->select([
                'id',
                'patient_id',
                'visit_record_id',
                'doctor_id',
                'note',
                'visibility',
                'created_at',
                'updated_at',
            ])
            ->where('patient_id', $patientId)
            ->where('doctor_id', $doctorId);

        if (! empty($filters['date_from'])) {
            $query->whereRaw('DATE(COALESCE(updated_at, created_at)) >= ?', [$filters['date_from']]);
        }

        if (! empty($filters['date_to'])) {
            $query->whereRaw('DATE(COALESCE(updated_at, created_at)) <= ?', [$filters['date_to']]);
        }

        $perPage = isset($filters['per_page'])
            ? min(max((int) $filters['per_page'], 1), 100)
            : 15;

        return $query
            ->orderByRaw('COALESCE(updated_at, created_at) DESC')
            ->paginate($perPage);
    }

    public function findForDoctor(int $noteId, int $doctorId): ?DoctorPrivateNote
    {
        return $this->model->newQuery()
            ->select([
                'id',
                'visit_record_id',
                'doctor_id',
                'note',
                'visibility',
                'created_at',
                'updated_at',
            ])
            ->where('doctor_id', $doctorId)
            ->find($noteId);
    }
}

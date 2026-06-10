<?php

namespace App\Modules\MedicalRecords\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\MedicalRecords\Models\MedicalRecord;

class MedicalRecordRepository extends BaseRepository
{
    public function __construct(MedicalRecord $model)
    {
        parent::__construct($model);
    }

    public function findByPatientId(int $patientId): ?MedicalRecord
    {
        return $this->model->newQuery()
            ->where('patient_id', $patientId)
            ->first();
    }

    public function ensureForPatient(int $patientId, ?int $actorId = null): MedicalRecord
    {
        return $this->model->newQuery()->firstOrCreate(
            ['patient_id' => $patientId],
            ['created_by' => $actorId, 'updated_by' => $actorId]
        );
    }

    public function updateLastVisit(int $patientId, array $data): MedicalRecord
    {
        $record = $this->ensureForPatient($patientId, $data['updated_by'] ?? null);

        $record->fill($data);
        $record->save();

        return $record->refresh();
    }
}

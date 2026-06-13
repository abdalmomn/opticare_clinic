<?php

namespace App\Modules\MedicalRecords\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\MedicalRecords\Models\VisitDiagnosisCode;

class VisitDiagnosisCodeRepository extends BaseRepository
{
    public function __construct(VisitDiagnosisCode $model)
    {
        parent::__construct($model);
    }

    public function syncForVisit(
        int $visitId,
        int $patientId,
        ?int $doctorId,
        ?int $createdBy,
        array $codeIds
    ): void {
        $codeIds = array_values(array_unique(array_map('intval', $codeIds)));

        $this->model->newQuery()
            ->where('visit_record_id', $visitId)
            ->when(
                ! empty($codeIds),
                fn ($q) => $q->whereNotIn('diagnosis_code_id', $codeIds)
            )
            ->delete();

        foreach ($codeIds as $codeId) {
            $this->model->newQuery()->updateOrCreate(
                [
                    'visit_record_id' => $visitId,
                    'diagnosis_code_id' => $codeId,
                ],
                [
                    'patient_id' => $patientId,
                    'doctor_id' => $doctorId,
                    'created_by' => $createdBy,
                ]
            );
        }
    }
}

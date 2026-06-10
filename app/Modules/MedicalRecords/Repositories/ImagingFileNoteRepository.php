<?php

namespace App\Modules\MedicalRecords\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\MedicalRecords\Models\ImagingFileNote;

class ImagingFileNoteRepository extends BaseRepository
{
    public function __construct(ImagingFileNote $model)
    {
        parent::__construct($model);
    }

    public function updateOrCreateForDoctor(
        int $imagingFileId,
        int $patientId,
        int $doctorId,
        array $data
    ): ImagingFileNote {
        return $this->model->newQuery()->updateOrCreate(
            [
                'imaging_file_id' => $imagingFileId,
                'doctor_id' => $doctorId,
            ],
            array_merge($data, [
                'patient_id' => $patientId,
            ])
        )->load('doctor:id,name');
    }
}

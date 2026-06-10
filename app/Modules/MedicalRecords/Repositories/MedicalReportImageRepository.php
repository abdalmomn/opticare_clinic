<?php

namespace App\Modules\MedicalRecords\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\MedicalRecords\Models\MedicalReportImage;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class MedicalReportImageRepository extends BaseRepository
{
    public function __construct(MedicalReportImage $model)
    {
        parent::__construct($model);
    }

    public function attachedFileIds(int $reportId): array
    {
        return $this->model->newQuery()
            ->where('medical_report_id', $reportId)
            ->whereNotNull('imaging_file_id')
            ->pluck('imaging_file_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function attachFile(int $reportId, int $imagingRequestId, int $imagingFileId): MedicalReportImage
    {
        return $this->model->newQuery()->firstOrCreate(
            [
                'medical_report_id' => $reportId,
                'imaging_file_id' => $imagingFileId,
            ],
            [
                'imaging_request_id' => $imagingRequestId,
            ]
        );
    }

    public function deleteForReport(int $reportId): void
    {
        $this->model->newQuery()
            ->where('medical_report_id', $reportId)
            ->delete();
    }

    public function listForReport(int $reportId): EloquentCollection
    {
        return $this->model->newQuery()
            ->where('medical_report_id', $reportId)
            ->with([
                'imagingFile:id,imaging_request_id,image_type,modality,image_label,region,eye,file_name,file_path,thumbnail_path',
            ])
            ->orderBy('id')
            ->get();
    }
}

<?php

namespace App\Modules\MedicalRecords\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\MedicalRecords\Models\MedicalReport;
use Illuminate\Pagination\LengthAwarePaginator;


class MedicalReportRepository extends BaseRepository
{
    public function __construct(MedicalReport $model)
    {
        parent::__construct($model);
    }

    public function updateOrCreateForVisit(int $visitId, array $data): MedicalReport
    {
        return $this->model->newQuery()->updateOrCreate(
            ['visit_record_id' => $visitId],
            $data
        );
    }

    public function replaceImages(MedicalReport $report, array $images): void
    {
        $report->images()->delete();

        if (empty($images)) {
            return;
        }

        $report->images()->createMany($images);
    }

    public function finalizeForVisit(int $visitId): void
    {
        $this->model->newQuery()
            ->where('visit_record_id', $visitId)
            ->where('status', MedicalReport::STATUS_DRAFT)
            ->update([
                'status' => MedicalReport::STATUS_FINALIZED,
                'finalized_at' => now(),
            ]);
    }

    public function timeline(int $patientId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->select([
                'id',
                'visit_record_id',
                'doctor_id',
                'title',
                'report_text',
                'status',
                'created_at',
            ])
            ->with('doctor:id,name')
            ->withCount('images')
            ->where('patient_id', $patientId);

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $perPage = isset($filters['per_page'])
            ? min(max((int) $filters['per_page'], 1), 100)
            : 15;

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}

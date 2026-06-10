<?php

namespace App\Modules\MedicalRecords\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\Imaging\Models\ImagingFile;
use App\Modules\Imaging\Models\ImagingRequest;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;


class MedicalRecordImagingRepository extends BaseRepository
{
    public const RESOLVED_TYPE_SQL = "COALESCE(NULLIF(imaging_files.image_type, ''), imaging_files.modality)";

    public function __construct(ImagingRequest $model)
    {
        parent::__construct($model);
    }

    public function foldersForPatient(int $patientId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->select([
                'id',
                'patient_id',
                'requested_by',
                'request_type',
                'status',
                'created_at',
                'completed_at',
            ])
            ->with([
                'requestedBy:id,name',
                'files:id,imaging_request_id,image_type,modality',
            ])
            ->withCount('files')
            ->where('patient_id', $patientId)
            ->where('status', $filters['status'] ?? 'completed');

        if (! empty($filters['image_type'])) {
            $query->whereHas('files', fn ($q) => $this->matchImageType($q, $filters['image_type']));
        }

        if (! empty($filters['eye'])) {
            $query->whereHas('files', fn ($q) => $q->where('eye', $filters['eye']));
        }

        if (! empty($filters['region'])) {
            $query->whereHas('files', fn ($q) => $q->where('region', $filters['region']));
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $perPage = $this->resolvePerPage($filters);

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function findFolder(int $folderId, array $options = []): ?ImagingRequest
    {
        return $this->model->newQuery()
            ->with([
                'requestedBy:id,name',
                'files' => function ($q) use ($options) {
                    if (! empty($options['image_type'])) {
                        $this->matchImageType($q, $options['image_type']);
                    }
                    if (! empty($options['eye'])) {
                        $q->where('eye', $options['eye']);
                    }
                    if (! empty($options['region'])) {
                        $q->where('region', $options['region']);
                    }
                    $q->orderBy('captured_at');
                },
                'files.doctorNotes' => function ($q) use ($options) {
                    if (array_key_exists('doctor_id', $options) && $options['doctor_id'] !== null) {
                        $q->where('doctor_id', $options['doctor_id']);
                    }
                },
            ])
            ->find($folderId);
    }

    public function imageTypesForPatient(int $patientId, array $filters = []): Collection
    {
        $baseQuery = ImagingFile::query()
            ->join('imaging_requests as ir', 'ir.id', '=', 'imaging_files.imaging_request_id')
            ->where('ir.patient_id', $patientId)
            ->where('ir.status', $filters['status'] ?? 'completed')
            ->selectRaw(self::RESOLVED_TYPE_SQL . ' as resolved_type')
            ->selectRaw('imaging_files.imaging_request_id')
            ->selectRaw('imaging_files.captured_at');

        if (! empty($filters['date_from'])) {
            $baseQuery->whereDate('imaging_files.captured_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $baseQuery->whereDate('imaging_files.captured_at', '<=', $filters['date_to']);
        }

        return DB::query()
            ->fromSub($baseQuery->toBase(), 'image_types')
            ->select('resolved_type')
            ->selectRaw('COUNT(*) as files_count')
            ->selectRaw('COUNT(DISTINCT imaging_request_id) as folders_count')
            ->selectRaw('MAX(captured_at) as latest_captured_at')
            ->whereNotNull('resolved_type')
            ->where('resolved_type', '!=', '')
            ->groupBy('resolved_type')
            ->orderBy('resolved_type')
            ->get();
    }

    public function findFile(int $fileId, ?int $doctorId = null): ?ImagingFile
    {
        return ImagingFile::query()
            ->with([
                'imagingRequest:id,patient_id,request_type,requested_by,created_at',
                'imagingRequest.requestedBy:id,name',
                'doctorNotes' => function ($q) use ($doctorId) {
                    if ($doctorId !== null) {
                        $q->where('doctor_id', $doctorId);
                    }
                },
            ])
            ->find($fileId);
    }

    public function findFilesByIds(array $ids, ?int $doctorId = null): EloquentCollection
    {
        return ImagingFile::query()
            ->with([
                'imagingRequest:id,patient_id,request_type,created_at',
                'doctorNotes' => function ($q) use ($doctorId) {
                    if ($doctorId !== null) {
                        $q->where('doctor_id', $doctorId);
                    }
                },
            ])
            ->whereIn('id', $ids)
            ->get();
    }

    public function findFilesByFolderIds(array $folderIds): EloquentCollection
    {
        return ImagingFile::query()
            ->whereIn('imaging_request_id', $folderIds)
            ->get();
    }

    public function matchImageType($query, string $type)
    {
        return $query->where(function ($w) use ($type) {
            $w->where('image_type', $type)
                ->orWhere(function ($x) use ($type) {
                    $x->where(function ($n) {
                        $n->whereNull('image_type')->orWhere('image_type', '');
                    })->where('modality', $type);
                });
        });
    }

    private function resolvePerPage(array $filters): int
    {
        return isset($filters['per_page'])
            ? min(max((int) $filters['per_page'], 1), 100)
            : 15;
    }
}

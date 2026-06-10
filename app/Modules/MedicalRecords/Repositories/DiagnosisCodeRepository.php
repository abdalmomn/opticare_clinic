<?php

namespace App\Modules\MedicalRecords\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\MedicalRecords\Models\DiagnosisCode;
use Illuminate\Pagination\LengthAwarePaginator;

class DiagnosisCodeRepository extends BaseRepository
{
    public function __construct(DiagnosisCode $model)
    {
        parent::__construct($model);
    }

    public function search(array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        $includeInactive = array_key_exists('is_active', $filters)
            && $filters['is_active'] !== null
            && ! filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN);

        if (! $includeInactive) {
            $query->where('is_active', true);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = isset($filters['per_page'])
            ? min(max((int) $filters['per_page'], 1), 100)
            : 25;

        return $query->select('id', 'code', 'name_en', 'name_ar', 'description')->orderBy('code', 'asc')->paginate($perPage);
    }
}

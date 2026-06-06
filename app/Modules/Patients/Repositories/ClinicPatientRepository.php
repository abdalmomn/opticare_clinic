<?php

namespace App\Modules\Patients\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\Patients\Models\ClinicPatient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClinicPatientRepository extends BaseRepository
{
    public function __construct(ClinicPatient $model)
    {
        parent::__construct($model);
    }

    public function search(array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (! empty($filters['keyword'])) {
            $keyword = trim((string) $filters['keyword']);

            $query->where(function ($q) use ($keyword) {
                $q->where('full_name', 'like', "%{$keyword}%")
                    ->orWhere('first_name', 'like', "%{$keyword}%")
                    ->orWhere('father_name', 'like', "%{$keyword}%")
                    ->orWhere('last_name', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('identity_number', 'like', "%{$keyword}%")
                    ->orWhere('medical_file_number', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%")
                    ->orWhere('national_id', 'like', "%{$keyword}%")
                    ->orWhere('passport_id', 'like', "%{$keyword}%");
            });
        }

        if (! empty($filters['identity_number'])) {
            $identityNumber = trim((string) $filters['identity_number']);

            $query->where(function ($q) use ($identityNumber) {
                $q->where('identity_number', $identityNumber)
                    ->orWhere('national_id', $identityNumber)
                    ->orWhere('passport_id', $identityNumber);
            });
        }

        if (! empty($filters['phone'])) {
            $phone = trim((string) $filters['phone']);

            $query->where('phone', 'like', "%{$phone}%");
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $isActive = filter_var(
                $filters['is_active'],
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if ($isActive !== null) {
                $query->where('is_active', $isActive);
            }
        }

        $perPage = isset($filters['per_page'])
            ? min(max((int) $filters['per_page'], 1), 100)
            : 15;

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['archive_reason'])) {
            $query->where('archive_reason', $filters['archive_reason']);
        }

        $includeArchived = filter_var(
            $filters['include_archived'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        if (! $includeArchived && empty($filters['status'])) {
            $query->whereNotIn('status', ['archived', 'deceased']);
        }
        
        return $query
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findByIdentity(string $identityNumber): ?ClinicPatient
    {
        return $this->model->newQuery()
            ->where('identity_number', $identityNumber)
            ->orWhere('national_id', $identityNumber)
            ->orWhere('passport_id', $identityNumber)
            ->first();
    }

    public function findPatientById(int $id): ?ClinicPatient
    {
        return $this->model->newQuery()->find($id);
    }

    public function identityExists(
        string $identityNumber,
        string $identityType,
        ?int $excludeId = null
    ): bool {
        $query = $this->model->newQuery()
            ->where(function ($q) use ($identityNumber, $identityType) {
                $q->where('identity_number', $identityNumber);

                if ($identityType === 'passport') {
                    $q->orWhere('passport_id', $identityNumber);
                } else {
                    $q->orWhere('national_id', $identityNumber);
                }
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function phoneExists(string $phone, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('phone', $phone);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function createPatient(array $data): ClinicPatient
    {
        return $this->model->newQuery()->create($data);
    }

    public function updatePatient(ClinicPatient $patient, array $data): ClinicPatient
    {
        $patient->update($data);

        return $patient->fresh();
    }

    public function nextFileNumber(): string
    {
        $year = now()->year;
        $prefix = "CP-{$year}-";

        $last = $this->model->newQuery()
            ->where('medical_file_number', 'like', "{$prefix}%")
            ->orderByDesc('medical_file_number')
            ->value('medical_file_number');

        $sequence = $last
            ? ((int) substr($last, strlen($prefix))) + 1
            : 1;

        return $prefix . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }
}

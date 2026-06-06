<?php

namespace App\Modules\Patients\Helpers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClinicPatientHelper
{
    public static function buildFullName(
        ?string $firstName,
        ?string $fatherName,
        ?string $lastName
    ): string {
        return trim(implode(' ', array_filter(
            [$firstName, $fatherName, $lastName],
            fn ($value) => filled(trim((string) $value))
        )));
    }

    public static function legacyIdentityColumn(string $identityType): string
    {
        return $identityType === 'passport' ? 'passport_id' : 'national_id';
    }

    public static function prepareCreateData(
        array $requestData,
        string $fileNumber,
        int $createdBy
    ): array {
        $fullName = self::buildFullName(
            $requestData['first_name'] ?? null,
            $requestData['father_name'] ?? null,
            $requestData['last_name'] ?? null
        );

        $identityType = $requestData['identity_type'];
        $identityNumber = $requestData['identity_number'];

        return array_merge($requestData, [
            'full_name'           => $fullName,
            'medical_file_number' => $fileNumber,

            'is_active' => true,
            'status'    => 'active',

            'name'       => $fullName,
            'birth_date' => $requestData['date_of_birth'] ?? null,

            'national_id' => $identityType === 'national_id'
                ? $identityNumber
                : null,

            'passport_id' => $identityType === 'passport'
                ? $identityNumber
                : null,

            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ]);
    }

    public static function prepareUpdateData(
        array $requestData,
        array $currentData,
        int $updatedBy
    ): array {
        $nameChanged = self::hasAnyKey($requestData, [
            'first_name',
            'father_name',
            'last_name',
        ]);

        if ($nameChanged) {
            $firstName = array_key_exists('first_name', $requestData)
                ? $requestData['first_name']
                : ($currentData['first_name'] ?? null);

            $fatherName = array_key_exists('father_name', $requestData)
                ? $requestData['father_name']
                : ($currentData['father_name'] ?? null);

            $lastName = array_key_exists('last_name', $requestData)
                ? $requestData['last_name']
                : ($currentData['last_name'] ?? null);

            if (blank($firstName) && blank($fatherName) && blank($lastName)) {
                $fullName = $currentData['full_name']
                    ?? $currentData['name']
                    ?? '';
            } else {
                $fullName = self::buildFullName($firstName, $fatherName, $lastName);
            }

            $requestData['full_name'] = $fullName;
            $requestData['name'] = $fullName;
        }

        if (array_key_exists('date_of_birth', $requestData)) {
            $requestData['birth_date'] = $requestData['date_of_birth'];
        }

        if (self::hasAnyKey($requestData, ['identity_type', 'identity_number'])) {
            $identityType = $requestData['identity_type']
                ?? $currentData['identity_type']
                ?? 'national_id';

            $identityNumber = $requestData['identity_number']
                ?? $currentData['identity_number']
                ?? ($identityType === 'passport'
                    ? ($currentData['passport_id'] ?? null)
                    : ($currentData['national_id'] ?? null));

            if ($identityNumber !== null) {
                $requestData['identity_type'] = $identityType;
                $requestData['identity_number'] = $identityNumber;

                $requestData['national_id'] = $identityType === 'national_id'
                    ? $identityNumber
                    : null;

                $requestData['passport_id'] = $identityType === 'passport'
                    ? $identityNumber
                    : null;
            }
        }

        if (array_key_exists('is_active', $requestData)) {
            $requestData['status'] = $requestData['is_active'] ? 'active' : 'inactive';
        }

        $requestData['updated_by'] = $updatedBy;

        return $requestData;
    }

    public static function formatPaginated(LengthAwarePaginator $paginator): array
    {
        return [
            'items' => $paginator->items(),
            'pagination' => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'has_more'     => $paginator->hasMorePages(),
            ],
        ];
    }

    private static function hasAnyKey(array $data, array $keys): bool
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return true;
            }
        }

        return false;
    }
}

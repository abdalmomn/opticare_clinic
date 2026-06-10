<?php

namespace App\Modules\MedicalRecords\Services;

use App\Modules\Authentication\Models\Staff;
use App\Modules\MedicalRecords\Repositories\DiagnosisCodeRepository;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DiagnosisCodeService
{
    public function __construct(
        protected DiagnosisCodeRepository $repository
    ) {}

    public function search(array $filters, Staff $actor): array
    {
        if (! AccessControlHelper::staffHasPermission($actor, PermissionList::VIEW_DISEASE_CLASSIFICATION)) {
            throw new HttpException(
                Response::HTTP_FORBIDDEN,
                __('medical_record.errors.not_allowed_view_diagnosis_codes')
            );
        }

        $paginator = $this->repository->search($filters);

        return [
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ];
    }
}

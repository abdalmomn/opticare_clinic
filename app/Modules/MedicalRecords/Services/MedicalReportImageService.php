<?php

namespace App\Modules\MedicalRecords\Services;

use App\Modules\Authentication\Models\Staff;
use App\Modules\MedicalRecords\Helpers\MedicalRecordHelper;
use App\Modules\MedicalRecords\Helpers\MedicalRecordImagesHelper;
use App\Modules\MedicalRecords\Models\MedicalReport;
use App\Modules\MedicalRecords\Models\MedicalReportImage;
use App\Modules\MedicalRecords\Repositories\MedicalRecordImagingRepository;
use App\Modules\MedicalRecords\Repositories\MedicalReportImageRepository;
use App\Modules\MedicalRecords\Repositories\MedicalReportRepository;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MedicalReportImageService
{
    public function __construct(
        protected MedicalReportRepository $reports,
        protected MedicalRecordImagingRepository $imaging,
        protected MedicalReportImageRepository $reportImages,
    ) {}


    private function authorize(Staff $actor, string $permission, string $messageKey): void
    {
        if (! AccessControlHelper::staffHasPermission($actor, $permission)) {
            throw new HttpException(Response::HTTP_FORBIDDEN, __($messageKey));
        }
    }

    public function attachToReport(int $reportId, array $data, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::CREATE_REPORT, 'medical_record.errors.not_allowed_attach_images');

        $report = $this->reports->findById($reportId);

        if (! $report) {
            throw new HttpException(Response::HTTP_NOT_FOUND, __('medical_record.errors.report_not_found'));
        }

        MedicalRecordImagesHelper::ensurePatientAccessible($actor, (int) $report->patient_id);

        $items = $this->linkSelections(
            $report,
            $data['imaging_file_ids'] ?? [],
            $data['imaging_request_ids'] ?? [],
            $data['mode'] ?? 'append'
        );

        return [
            'report_id' => $report->id,
            'attached_images_count' => count($items),
            'items' => $items,
        ];
    }

    public function linkSelections(
        MedicalReport $report,
        array $fileIds = [],
        array $folderIds = [],
        string $mode = 'append'
    ): array {
        $fileIds = array_values(array_unique(array_map('intval', $fileIds)));
        $folderIds = array_values(array_unique(array_map('intval', $folderIds)));

        return DB::transaction(function () use ($report, $fileIds, $folderIds, $mode) {
            if ($mode === 'replace') {
                $this->reportImages->deleteForReport($report->id);
            }

            $pairs = $this->collectPairs($report, $fileIds, $folderIds);

            foreach ($pairs as $pair) {
                $this->reportImages->attachFile($report->id, $pair['request_id'], $pair['file_id']);
            }

            return $this->reportImages->listForReport($report->id)
                ->map(fn (MedicalReportImage $row): array => MedicalReportImage::formatAttachedImage($row))
                ->all();
        });
    }

    private function collectPairs(MedicalReport $report, array $fileIds, array $folderIds): array
    {
        $patientId = (int) $report->patient_id;
        $pairs = [];

        $files = collect();

        if (! empty($fileIds)) {
            $files = $files->merge($this->imaging->findFilesByIds($fileIds));
        }

        if (! empty($folderIds)) {
            $files = $files->merge($this->imaging->findFilesByFolderIds($folderIds));
        }

        foreach ($files as $file) {
            $filePatientId = (int) ($file->imagingRequest->patient_id ?? 0);

            if ($filePatientId !== $patientId) {
                throw new HttpException(
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    __('medical_record.errors.image_not_for_patient')
                );
            }

            $pairs[$file->id] = [
                'request_id' => (int) $file->imaging_request_id,
                'file_id' => (int) $file->id,
            ];
        }

        return array_values($pairs);
    }
}

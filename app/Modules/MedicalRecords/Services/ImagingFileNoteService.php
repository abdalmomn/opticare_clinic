<?php

namespace App\Modules\MedicalRecords\Services;

use App\Modules\Authentication\Models\Staff;
use App\Modules\MedicalRecords\Helpers\MedicalRecordImagesHelper;
use App\Modules\MedicalRecords\Repositories\ImagingFileNoteRepository;
use App\Modules\MedicalRecords\Repositories\MedicalRecordImagingRepository;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ImagingFileNoteService
{
    public function __construct(
        protected ImagingFileNoteRepository $notes,
        protected MedicalRecordImagingRepository $imaging,
    ) {}

    private function authorize(Staff $actor, string $permission, string $messageKey): void
    {
        if (! AccessControlHelper::staffHasPermission($actor, $permission)) {
            throw new HttpException(Response::HTTP_FORBIDDEN, __($messageKey));
        }
    }

    public function saveNote(int $fileId, array $data, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::EDIT_MEDICAL_RECORDS, 'medical_record.errors.not_allowed_save_image_note');

        $file = $this->imaging->findFile($fileId);

        if (! $file) {
            throw new HttpException(Response::HTTP_NOT_FOUND, __('medical_record.errors.file_not_found'));
        }

        $patientId = (int) ($file->imagingRequest->patient_id ?? 0);

        MedicalRecordImagesHelper::ensurePatientAccessible($actor, $patientId);

        $note = $data['note'] ?? null;
        if ($note === '') {
            $note = null;
        }

        $record = $this->notes->updateOrCreateForDoctor(
            $file->id,
            $patientId,
            (int) $actor->id,
            [
                'note' => $note,
                'visit_record_id' => $data['visit_record_id'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]
        );

        return MedicalRecordImagesHelper::formatNote($record);
    }


}

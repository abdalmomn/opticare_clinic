<?php

namespace App\Modules\MedicalRecords\Helpers;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Authentication\Models\Staff;
use App\Modules\Imaging\Models\ImagingFile;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\MedicalRecords\Models\ImagingFileNote;
use App\Modules\RolesPermissions\Enums\RoleEnum;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class MedicalRecordImagesHelper {

    public static function formatNote(ImagingFileNote $note): array
    {
        return [
            'id' => $note->id,
            'imaging_file_id' => $note->imaging_file_id,
            'patient_id' => $note->patient_id,
            'doctor' => $note->doctor ? [
                'id' => $note->doctor->id,
                'name' => $note->doctor->name,
            ] : null,
            'visit_record_id' => $note->visit_record_id,
            'note' => $note->note,
            'updated_at' => optional($note->updated_at)->toISOString(),
        ];
    }

    public static function formatFolderItem(ImagingRequest $folder): array
    {
        $imagesCount = (int) ($folder->files_count ?? 0);

        $countsByType = [];
        foreach ($folder->files as $file) {
            $type = self::resolvedType($file);
            if ($type !== null) {
                $countsByType[$type] = ($countsByType[$type] ?? 0) + 1;
            }
        }

        return [
            'id' => $folder->id,
            'timeline_type' => 'imaging',
            'folder_label' => optional($folder->created_at)->format('M Y'),
            'date' => optional($folder->created_at)->toISOString(),
            'request_type' => $folder->request_type,
            'status' => $folder->status,
            'images_count' => $imagesCount,
            'available_types' => array_keys($countsByType),
            'files_count_by_type' => $countsByType,
            'doctor' => self::formatDoctor($folder->requestedBy),
            'is_selectable_for_view' => $imagesCount > 0,
            'is_selectable_for_report' => $imagesCount > 0,
            'is_selectable_for_compare' => $imagesCount > 0,
        ];
    }

    public static function formatFolderHeader(ImagingRequest $folder): array
    {
        return [
            'id' => $folder->id,
            'label' => optional($folder->created_at)->format('M Y'),
            'request_type' => $folder->request_type,
            'date' => optional($folder->created_at)->toISOString(),
            'doctor' => self::formatDoctor($folder->requestedBy),
        ];
    }

    public static function formatFile(ImagingFile $file): array
    {
        return [
            'id' => $file->id,
            'label' => self::fileLabel($file),
            'image_type' => self::resolvedType($file),
            'modality' => $file->modality,
            'eye' => $file->eye,
            'region' => $file->region,
            'file_name' => $file->file_name,
            'file_url' => self::fileUrl($file->file_path),
            'thumbnail_url' => self::thumbnailUrl($file),
            'captured_at' => optional($file->captured_at)->toISOString(),
            'notes' => self::noteFor($file),
            'is_selectable_for_view' => true,
            'is_selectable_for_report' => true,
            'is_selectable_for_compare' => true,
        ];
    }

    public static function formatComparisonSide(ImagingRequest $folder, string $title): array
    {
        return [
            'folder' => [
                'id' => $folder->id,
                'label' => optional($folder->created_at)->format('M Y'),
                'date' => optional($folder->created_at)->toISOString(),
                'title' => $title,
            ],
            'files' => $folder->files->map(fn (ImagingFile $file): array => $this->formatComparisonFile($file))->all(),
        ];
    }

    public static function formatComparisonFile(ImagingFile $file): array
    {
        return [
            'id' => $file->id,
            'label' => self::fileLabel($file),
            'image_type' => self::resolvedType($file),
            'modality' => $file->modality,
            'eye' => $file->eye,
            'region' => $file->region,
            'file_url' => self::fileUrl($file->file_path),
            'thumbnail_url' => self::thumbnailUrl($file),
            'notes' => self::noteFor($file),
        ];
    }

    public static function formatComparisonFileWithFolder(ImagingFile $file): array
    {
        $folder = $file->imagingRequest;

        return array_merge(self::formatComparisonFile($file), [
            'folder' => $folder ? [
                'id' => $folder->id,
                'label' => optional($folder->created_at)->format('M Y'),
                'date' => optional($folder->created_at)->toISOString(),
            ] : null,
        ]);
    }

    public static function formatDoctor(?Staff $staff): ?array
    {
        if (! $staff) {
            return null;
        }

        return ['id' => $staff->id, 'name' => $staff->name];
    }

    public static function resolvedType(ImagingFile $file): ?string
    {
        if ($file->image_type !== null && $file->image_type !== '') {
            return $file->image_type;
        }

        return ($file->modality !== null && $file->modality !== '') ? $file->modality : null;
    }

    public static function fileLabel(ImagingFile $file): ?string
    {
        if (! empty($file->image_label)) {
            return $file->image_label;
        }

        if (! empty($file->region) && ! empty($file->eye)) {
            return $file->region . ' ' . $file->eye;
        }

        if (! empty($file->modality)) {
            return $file->modality;
        }

        return $file->file_name ? pathinfo($file->file_name, PATHINFO_FILENAME) : null;
    }

    public static function thumbnailUrl(ImagingFile $file): ?string
    {
        if (! empty($file->thumbnail_path)) {
            return self::fileUrl($file->thumbnail_path);
        }

        return self::fileUrl($file->file_path);
    }

    public static function fileUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    public static function noteFor(ImagingFile $file): ?string
    {
        if (! $file->relationLoaded('doctorNotes')) {
            return null;
        }

        return optional($file->doctorNotes->first())->note;
    }

    public static function nullableMatch($a, $b): ?bool
    {
        if ($a === null || $b === null || $a === '' || $b === '') {
            return null;
        }

        return $a === $b;
    }


    public static function ensurePatientAccessible(Staff $actor, int $patientId): void
    {
        if ($actor->hasRole(RoleEnum::MEDICAL_CENTER_ADMIN->value, 'api')) {
            return;
        }

        if ($actor->hasRole(RoleEnum::DOCTOR->value, 'api')) {
            if (! config('opticare.is_medical_center', false)) {
                return;
            }

            $owns = Appointment::query()
                ->where('patient_id', $patientId)
                ->where('doctor_id', $actor->id)
                ->exists();

            if (! $owns) {
                throw new HttpException(Response::HTTP_FORBIDDEN, __('medical_record.errors.not_allowed_view_record'));
            }
        }
    }

    public static function authorize(Staff $actor, string $permission, string $messageKey): void
    {
        if (! AccessControlHelper::staffHasPermission($actor, $permission)) {
            throw new HttpException(Response::HTTP_FORBIDDEN, __($messageKey));
        }
    }

    public static function formatPaginated($paginator): array
    {
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

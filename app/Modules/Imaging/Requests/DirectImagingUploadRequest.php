<?php

namespace App\Modules\Imaging\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DirectImagingUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxKilobytes = (int) config('opticare.imaging_max_upload_kb', 20480);

        return [
            'patient_id' => 'required|integer|exists:clinic_patients,id',
            'visit_record_id' => 'nullable|integer|exists:visit_records,id',
            'appointment_id' => 'nullable|integer|exists:appointments,id',
            'notes' => 'nullable|string|max:2000',
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|mimes:jpg,jpeg,png,gif,bmp,webp,tif,tiff,pdf|max:'.$maxKilobytes,
            'metadata' => 'required|array|min:1',
            'metadata.*.image_type' => 'required|string|max:100',
            'metadata.*.modality' => 'nullable|string|max:100',
            'metadata.*.eye' => 'nullable|string|max:20',
            'metadata.*.region' => 'nullable|string|max:100',
            'metadata.*.image_label' => 'nullable|string|max:255',
            'metadata.*.captured_at' => 'nullable|date',
            'metadata.*.device_id' => 'nullable|integer|exists:clinic_devices,id',
            'metadata.*.is_primary' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => __('imaging.validation.patient_id_required'),
            'patient_id.exists' => __('imaging.validation.patient_id_invalid'),
            'visit_record_id.exists' => __('imaging.validation.visit_record_id_invalid'),
            'appointment_id.exists' => __('imaging.validation.appointment_id_invalid'),
            'notes.max' => __('imaging.validation.notes_max'),
            'files.required' => __('imaging.validation.files_required'),
            'files.array' => __('imaging.validation.files_array'),
            'files.min' => __('imaging.validation.files_min'),
            'files.*.file' => __('imaging.validation.file_invalid'),
            'files.*.mimes' => __('imaging.validation.file_type_invalid'),
            'files.*.max' => __('imaging.validation.file_too_large'),
            'metadata.required' => __('imaging.validation.metadata_required'),
            'metadata.array' => __('imaging.validation.metadata_array'),
            'metadata.*.image_type.required' => __('imaging.validation.image_type_required'),
            'metadata.*.image_type.max' => __('imaging.validation.image_type_max'),
            'metadata.*.modality.max' => __('imaging.validation.modality_max'),
            'metadata.*.eye.max' => __('imaging.validation.requested_type_eye_max'),
            'metadata.*.region.max' => __('imaging.validation.requested_type_region_max'),
            'metadata.*.image_label.max' => __('imaging.validation.image_label_max'),
            'metadata.*.captured_at.date' => __('imaging.validation.captured_at_invalid'),
            'metadata.*.device_id.exists' => __('imaging.validation.device_id_invalid'),
        ];
    }
}

<?php

namespace App\Modules\Imaging\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadImagingFilesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxKilobytes = (int) config('opticare.imaging_max_upload_kb', 20480);

        return [
            'device_id' => 'required|integer|exists:clinic_devices,id',
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|mimes:jpg,jpeg,png,gif,bmp,webp,tif,tiff,pdf|max:'.$maxKilobytes,
            'metadata' => 'required|array|min:1',
            'metadata.*.image_type' => 'required|string|max:100',
            'metadata.*.modality' => 'nullable|string|max:100',
            'metadata.*.eye' => 'nullable|string|max:20',
            'metadata.*.region' => 'nullable|string|max:100',
            'metadata.*.image_label' => 'nullable|string|max:255',
            'metadata.*.captured_at' => 'nullable|date',
            'metadata.*.imaging_request_item_id' => 'nullable|integer|exists:imaging_request_items,id',
            'metadata.*.is_primary' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'device_id.required' => __('imaging.validation.device_id_required'),
            'device_id.exists' => __('imaging.validation.device_id_invalid'),
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
            'metadata.*.imaging_request_item_id.exists' => __('imaging.validation.item_id_invalid'),
        ];
    }
}

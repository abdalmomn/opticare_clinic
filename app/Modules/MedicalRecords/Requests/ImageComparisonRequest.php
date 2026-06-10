<?php

namespace App\Modules\MedicalRecords\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImageComparisonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image_type' => 'required|string|max:50',
            'left_folder_id' => 'required|integer|different:right_folder_id|exists:imaging_requests,id',
            'right_folder_id' => 'required|integer|exists:imaging_requests,id',
        ];
    }

    public function messages(): array
    {
        return [
            'image_type.required' => __('medical_record.validation.image_type_required'),
            'left_folder_id.required' => __('medical_record.validation.left_folder_required'),
            'right_folder_id.required' => __('medical_record.validation.right_folder_required'),
            'left_folder_id.different' => __('medical_record.validation.folders_must_differ'),
            'left_folder_id.exists' => __('medical_record.validation.folder_invalid'),
            'right_folder_id.exists' => __('medical_record.validation.folder_invalid'),
        ];
    }
}

<?php

namespace App\Modules\MedicalRecords\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileComparisonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'left_file_id' => 'required|integer|different:right_file_id|exists:imaging_files,id',
            'right_file_id' => 'required|integer|exists:imaging_files,id',
        ];
    }

    public function messages(): array
    {
        return [
            'left_file_id.required' => __('medical_record.validation.left_file_required'),
            'right_file_id.required' => __('medical_record.validation.right_file_required'),
            'left_file_id.different' => __('medical_record.validation.files_must_differ'),
            'left_file_id.exists' => __('medical_record.validation.file_invalid'),
            'right_file_id.exists' => __('medical_record.validation.file_invalid'),
        ];
    }
}

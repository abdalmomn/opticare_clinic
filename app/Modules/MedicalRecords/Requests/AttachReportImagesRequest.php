<?php

namespace App\Modules\MedicalRecords\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachReportImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode' => ['nullable', 'string', Rule::in(['append', 'replace'])],
            'imaging_file_ids' => 'nullable|array',
            'imaging_file_ids.*' => 'integer|exists:imaging_files,id',
            'imaging_request_ids' => 'nullable|array',
            'imaging_request_ids.*' => 'integer|exists:imaging_requests,id',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (empty($this->input('imaging_file_ids')) && empty($this->input('imaging_request_ids'))) {
                $validator->errors()->add(
                    'imaging_file_ids',
                    __('medical_record.validation.attach_images_required')
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'mode.in' => __('medical_record.validation.mode_invalid'),
            'imaging_file_ids.*.exists' => __('medical_record.validation.file_invalid'),
            'imaging_request_ids.*.exists' => __('medical_record.validation.folder_invalid'),
        ];
    }
}

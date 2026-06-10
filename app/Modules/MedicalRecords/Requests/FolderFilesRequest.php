<?php

namespace App\Modules\MedicalRecords\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FolderFilesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image_type' => 'nullable|string|max:50',
            'eye' => ['nullable', 'string', Rule::in(['OD', 'OS', 'OU'])],
            'region' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'eye.in' => __('medical_record.validation.eye_invalid'),
        ];
    }
}

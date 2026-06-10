<?php

namespace App\Modules\MedicalRecords\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListImageFoldersRequest extends FormRequest
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
            'status' => ['nullable', 'string', Rule::in(['pending', 'in_progress', 'completed', 'canceled'])],
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'eye.in' => __('medical_record.validation.eye_invalid'),
            'status.in' => __('medical_record.validation.status_invalid'),
            'date_from.date' => __('medical_record.validation.date_invalid'),
            'date_to.date' => __('medical_record.validation.date_invalid'),
            'per_page.max' => __('medical_record.validation.per_page_max'),
        ];
    }
}

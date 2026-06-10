<?php

namespace App\Modules\MedicalRecords\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListTimelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'date_from.date' => __('medical_record.validation.date_invalid'),
            'date_to.date' => __('medical_record.validation.date_invalid'),
            'per_page.max' => __('medical_record.validation.per_page_max'),
        ];
    }
}

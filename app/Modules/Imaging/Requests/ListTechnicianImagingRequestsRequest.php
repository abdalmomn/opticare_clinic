<?php

namespace App\Modules\Imaging\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListTechnicianImagingRequestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'priority' => 'nullable|string|in:normal,urgent',
            'patient_id' => 'nullable|integer|exists:clinic_patients,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'search' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'priority.in' => __('imaging.validation.priority_invalid'),
            'patient_id.exists' => __('imaging.validation.patient_id_invalid'),
            'date_from.date' => __('imaging.validation.date_from_invalid'),
            'date_to.date' => __('imaging.validation.date_to_invalid'),
            'date_to.after_or_equal' => __('imaging.validation.date_to_before_from'),
            'search.max' => __('imaging.validation.search_max'),
            'per_page.max' => __('imaging.validation.per_page_max'),
            'page.min' => __('imaging.validation.page_min'),
        ];
    }
}

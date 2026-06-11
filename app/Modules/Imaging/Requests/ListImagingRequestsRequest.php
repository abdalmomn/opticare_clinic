<?php

namespace App\Modules\Imaging\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListImagingRequestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|string|in:requested,pending_payment,payment_confirmed,ready_for_imaging,in_progress,completed,cancelled,pending,canceled',
            'payment_status' => 'nullable|string|in:pending,confirmed,waived,refunded',
            'patient_id' => 'nullable|integer|exists:clinic_patients,id',
            'requested_by' => 'nullable|integer|exists:staff,id',
            'technician_id' => 'nullable|integer|exists:staff,id',
            'priority' => 'nullable|string|in:normal,urgent',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => __('imaging.validation.status_invalid'),
            'payment_status.in' => __('imaging.validation.payment_status_invalid'),
            'patient_id.exists' => __('imaging.validation.patient_id_invalid'),
            'requested_by.exists' => __('imaging.validation.requested_by_invalid'),
            'technician_id.exists' => __('imaging.validation.technician_id_invalid'),
            'priority.in' => __('imaging.validation.priority_invalid'),
            'date_from.date' => __('imaging.validation.date_from_invalid'),
            'date_to.date' => __('imaging.validation.date_to_invalid'),
            'date_to.after_or_equal' => __('imaging.validation.date_to_before_from'),
            'search.max' => __('imaging.validation.search_max'),
            'per_page.max' => __('imaging.validation.per_page_max'),
            'page.min' => __('imaging.validation.page_min'),
        ];
    }
}

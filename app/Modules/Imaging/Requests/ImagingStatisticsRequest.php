<?php

namespace App\Modules\Imaging\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImagingStatisticsRequest extends FormRequest
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
            'doctor_id' => 'nullable|integer|exists:staff,id',
            'technician_id' => 'nullable|integer|exists:staff,id',
            'room_id' => 'nullable|integer|exists:rooms,id',
            'device_id' => 'nullable|integer|exists:clinic_devices,id',
            'source' => 'nullable|string|in:doctor_request,secretary_request,doctor_upload,external',
        ];
    }

    public function messages(): array
    {
        return [
            'date_from.date' => __('imaging.validation.date_from_invalid'),
            'date_to.date' => __('imaging.validation.date_to_invalid'),
            'date_to.after_or_equal' => __('imaging.validation.date_to_before_from'),
            'doctor_id.exists' => __('imaging.validation.requested_by_invalid'),
            'technician_id.exists' => __('imaging.validation.technician_id_invalid'),
            'room_id.exists' => __('imaging.validation.room_id_invalid'),
            'device_id.exists' => __('imaging.validation.device_id_invalid'),
            'source.in' => __('imaging.validation.source_invalid'),
        ];
    }
}

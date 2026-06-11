<?php

namespace App\Modules\Imaging\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreImagingRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'required|integer|exists:clinic_patients,id',
            'visit_record_id' => 'nullable|integer|exists:visit_records,id',
            'appointment_id' => 'nullable|integer|exists:appointments,id',
            'requested_by' => 'nullable|integer|exists:staff,id',
            'room_id' => 'nullable|integer|exists:rooms,id',
            'notes' => 'nullable|string|max:2000',
            'priority' => 'nullable|string|in:normal,urgent',
            'requested_types' => 'required|array|min:1',
            'requested_types.*.image_type' => 'required|string|max:100',
            'requested_types.*.eye' => 'nullable|string|max:20',
            'requested_types.*.region' => 'nullable|string|max:100',
            'requested_types.*.notes' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => __('imaging.validation.patient_id_required'),
            'patient_id.exists' => __('imaging.validation.patient_id_invalid'),
            'visit_record_id.exists' => __('imaging.validation.visit_record_id_invalid'),
            'appointment_id.exists' => __('imaging.validation.appointment_id_invalid'),
            'requested_by.exists' => __('imaging.validation.requested_by_invalid'),
            'room_id.exists' => __('imaging.validation.room_id_invalid'),
            'priority.in' => __('imaging.validation.priority_invalid'),
            'requested_types.required' => __('imaging.validation.requested_types_required'),
            'requested_types.array' => __('imaging.validation.requested_types_array'),
            'requested_types.min' => __('imaging.validation.requested_types_min'),
            'requested_types.*.image_type.required' => __('imaging.validation.requested_type_image_type_required'),
            'requested_types.*.image_type.max' => __('imaging.validation.requested_type_image_type_max'),
            'requested_types.*.eye.max' => __('imaging.validation.requested_type_eye_max'),
            'requested_types.*.region.max' => __('imaging.validation.requested_type_region_max'),
            'requested_types.*.notes.max' => __('imaging.validation.requested_type_notes_max'),
        ];
    }
}

<?php

namespace App\Modules\Appointments\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'required|integer|exists:clinic_patients,id',
            'doctor_id' => 'nullable|integer|exists:staff,id',
            'appointment_at' => 'required|date|after_or_equal:now',
            'type' => 'required|in:consultation,follow_up,imaging,consultation_and_imaging,surgery_preparation',
            'reason' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => __('appointment.validation.patient_id_required'),
            'patient_id.exists' => __('appointment.validation.patient_id_invalid'),
            'doctor_id.exists' => __('appointment.validation.doctor_id_invalid'),
            'appointment_at.required' => __('appointment.validation.appointment_at_required'),
            'appointment_at.date' => __('appointment.validation.appointment_at_invalid'),
            'appointment_at.after_or_equal' => __('appointment.validation.appointment_at_past'),
            'type.required' => __('appointment.validation.type_required'),
            'type.in' => __('appointment.validation.type_invalid'),
            'reason.max' => __('appointment.validation.reason_max'),
            'notes.max' => __('appointment.validation.notes_max'),
        ];
    }
}

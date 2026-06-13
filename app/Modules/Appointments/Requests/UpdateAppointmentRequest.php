<?php

namespace App\Modules\Appointments\Requests;

use App\Modules\Appointments\Enums\AppointmentTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => 'sometimes|nullable|integer|exists:staff,id',
            'appointment_at' => 'sometimes|date|after_or_equal:now',
            'type' => ['sometimes', Rule::enum(AppointmentTypeEnum::class)],
            'reason' => 'sometimes|nullable|string|max:1000',
            'notes' => 'sometimes|nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'doctor_id.exists' => __('appointment.validation.doctor_id_invalid'),
            'appointment_at.date' => __('appointment.validation.appointment_at_invalid'),
            'appointment_at.after_or_equal' => __('appointment.validation.appointment_at_past'),
            'type.in' => __('appointment.validation.type_invalid'),
            'reason.max' => __('appointment.validation.reason_max'),
            'notes.max' => __('appointment.validation.notes_max'),
        ];
    }
}

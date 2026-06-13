<?php

namespace App\Modules\Appointments\Requests;

use App\Modules\Appointments\Enums\AppointmentStatusEnum;
use App\Modules\Appointments\Enums\AppointmentTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAppointmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'nullable|date',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'status' => ['nullable', Rule::enum(AppointmentStatusEnum::class)],
            'type' => [
                'sometimes',
                Rule::enum(AppointmentTypeEnum::class),
            ],
            'patient_id' => 'nullable|integer|exists:clinic_patients,id',
            'doctor_id' => 'nullable|integer|exists:staff,id',
            'keyword' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'date.date' => __('appointment.validation.date_invalid'),
            'date_from.date' => __('appointment.validation.date_from_invalid'),
            'date_to.date' => __('appointment.validation.date_to_invalid'),
            'date_to.after_or_equal' => __('appointment.validation.date_to_before_from'),
            'status.in' => __('appointment.validation.status_invalid'),
            'type.in' => __('appointment.validation.type_invalid'),
            'patient_id.exists' => __('appointment.validation.patient_id_invalid'),
            'doctor_id.exists' => __('appointment.validation.doctor_id_invalid'),
            'keyword.max' => __('appointment.validation.keyword_max'),
            'per_page.max' => __('appointment.validation.per_page_max'),
        ];
    }
}

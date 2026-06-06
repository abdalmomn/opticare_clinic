<?php

namespace App\Modules\Appointments\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => 'required|integer|exists:staff,id',
        ];
    }

    public function messages(): array
    {
        return [
            'doctor_id.required' => __('appointment.validation.doctor_id_required'),
            'doctor_id.exists' => __('appointment.validation.doctor_id_invalid'),
        ];
    }
}

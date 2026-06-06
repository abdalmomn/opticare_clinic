<?php

namespace App\Modules\Appointments\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cancel_reason' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'cancel_reason.required' => __('appointment.validation.cancel_reason_required'),
            'cancel_reason.max' => __('appointment.validation.cancel_reason_max'),
        ];
    }
}

<?php

namespace App\Modules\Appointments\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckInAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'notes.max' => __('appointment.validation.notes_max'),
        ];
    }
}

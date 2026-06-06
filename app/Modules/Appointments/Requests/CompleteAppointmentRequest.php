<?php

namespace App\Modules\Appointments\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'completion_notes' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'completion_notes.max' => __('appointment.validation.completion_notes_max'),
        ];
    }
}

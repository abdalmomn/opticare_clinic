<?php

namespace App\Modules\Imaging\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendImagingRequestToTechnicianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'technician_id' => 'required|integer|exists:staff,id',
            'room_id' => 'nullable|integer|exists:rooms,id',
            'priority' => 'nullable|string|in:normal,urgent',
        ];
    }

    public function messages(): array
    {
        return [
            'technician_id.exists' => __('imaging.validation.technician_id_invalid'),
            'room_id.exists' => __('imaging.validation.room_id_invalid'),
            'priority.in' => __('imaging.validation.priority_invalid'),
        ];
    }
}

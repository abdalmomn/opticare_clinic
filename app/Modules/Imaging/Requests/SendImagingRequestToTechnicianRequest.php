<?php

namespace App\Modules\Imaging\Requests;

use App\Modules\Imaging\Enums\ImagingRequestPriorityEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'priority' => ['nullable', Rule::enum(ImagingRequestPriorityEnum::class)],
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

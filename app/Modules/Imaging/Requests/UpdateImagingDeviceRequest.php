<?php

namespace App\Modules\Imaging\Requests;

use App\Modules\Clinic\Models\ClinicDevice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateImagingDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $device = $this->route('device');
        $deviceId = $device instanceof ClinicDevice ? $device->id : $device;

        return [
            'name' => 'sometimes|string|max:255',
            'device_identifier' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('clinic_devices', 'device_identifier')->ignore($deviceId),
            ],
            'serial_number' => 'sometimes|nullable|string|max:100',
            'device_type' => 'sometimes|string|max:100',
            'manufacturer' => 'sometimes|nullable|string|max:100',
            'model' => 'sometimes|nullable|string|max:100',
            'room_id' => 'sometimes|nullable|integer|exists:rooms,id',
            'status' => 'sometimes|string|in:active,maintenance,offline,retired',
            'last_maintenance_at' => 'sometimes|nullable|date',
            'notes' => 'sometimes|nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => __('imaging.validation.device_name_max'),
            'device_identifier.unique' => __('imaging.validation.device_identifier_taken'),
            'device_identifier.max' => __('imaging.validation.device_identifier_max'),
            'serial_number.max' => __('imaging.validation.serial_number_max'),
            'device_type.max' => __('imaging.validation.device_type_max'),
            'manufacturer.max' => __('imaging.validation.manufacturer_max'),
            'model.max' => __('imaging.validation.model_max'),
            'room_id.exists' => __('imaging.validation.room_id_invalid'),
            'status.in' => __('imaging.validation.device_status_invalid'),
            'last_maintenance_at.date' => __('imaging.validation.last_maintenance_at_invalid'),
            'notes.max' => __('imaging.validation.notes_max'),
        ];
    }
}

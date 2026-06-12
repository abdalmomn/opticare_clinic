<?php

namespace App\Modules\Imaging\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreImagingDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'device_identifier' => 'nullable|string|max:100|unique:clinic_devices,device_identifier',
            'serial_number' => 'nullable|string|max:100',
            'device_type' => 'required|string|max:100',
            'manufacturer' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'room_id' => 'nullable|integer|exists:rooms,id',
            'status' => 'nullable|string|in:active,maintenance,offline,retired',
            'last_maintenance_at' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('imaging.validation.device_name_required'),
            'name.max' => __('imaging.validation.device_name_max'),
            'device_identifier.unique' => __('imaging.validation.device_identifier_taken'),
            'device_identifier.max' => __('imaging.validation.device_identifier_max'),
            'serial_number.max' => __('imaging.validation.serial_number_max'),
            'device_type.required' => __('imaging.validation.device_type_required'),
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

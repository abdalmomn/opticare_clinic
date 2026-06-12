<?php

namespace App\Modules\Imaging\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListImagingDevicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|string|in:active,maintenance,offline,retired',
            'room_id' => 'nullable|integer|exists:rooms,id',
            'device_type' => 'nullable|string|max:100',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => __('imaging.validation.device_status_invalid'),
            'room_id.exists' => __('imaging.validation.room_id_invalid'),
            'device_type.max' => __('imaging.validation.device_type_max'),
            'search.max' => __('imaging.validation.search_max'),
            'per_page.max' => __('imaging.validation.per_page_max'),
            'page.min' => __('imaging.validation.page_min'),
        ];
    }
}

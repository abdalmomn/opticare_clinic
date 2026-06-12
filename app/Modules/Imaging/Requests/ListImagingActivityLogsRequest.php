<?php

namespace App\Modules\Imaging\Requests;

use App\Modules\Imaging\Models\ImagingActivityLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListImagingActivityLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'imaging_request_id' => 'nullable|integer|exists:imaging_requests,id',
            'imaging_file_id' => 'nullable|integer|exists:imaging_files,id',
            'actor_id' => 'nullable|integer|exists:staff,id',
            'action' => ['nullable', 'string', Rule::in(ImagingActivityLog::actions())],
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'imaging_request_id.exists' => __('imaging.validation.imaging_request_id_invalid'),
            'imaging_file_id.exists' => __('imaging.validation.imaging_file_id_invalid'),
            'actor_id.exists' => __('imaging.validation.actor_id_invalid'),
            'action.in' => __('imaging.validation.action_invalid'),
            'date_from.date' => __('imaging.validation.date_from_invalid'),
            'date_to.date' => __('imaging.validation.date_to_invalid'),
            'date_to.after_or_equal' => __('imaging.validation.date_to_before_from'),
            'per_page.max' => __('imaging.validation.per_page_max'),
            'page.min' => __('imaging.validation.page_min'),
        ];
    }
}

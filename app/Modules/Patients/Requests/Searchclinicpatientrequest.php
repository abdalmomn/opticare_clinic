<?php

namespace App\Modules\Patients\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchClinicPatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword'          => ['nullable', 'string', 'max:255'],
            'identity_number'  => ['nullable', 'string', 'max:50'],
            'phone'            => ['nullable', 'string', 'max:30'],
            'is_active'        => ['nullable', 'boolean'],
            'per_page'         => ['nullable', 'integer', 'min:1', 'max:100'],
            'status'           => ['nullable', 'in:active,inactive,archived,deceased'],
            'include_archived' => ['nullable', 'boolean'],
            'archive_reason'   => ['nullable', 'in:no_longer_patient,transferred,duplicate,deceased,other'],
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.max'         => __('patient.validation.keyword_max'),
            'identity_number.max' => __('patient.validation.identity_number_max'),
            'phone.max'           => __('patient.validation.phone_max'),
            'per_page.integer'    => __('patient.validation.per_page_integer'),
            'per_page.min'        => __('patient.validation.per_page_min'),
            'per_page.max'        => __('patient.validation.per_page_max'),
            'status.in' => __('patient.validation.status_in'),
            'include_archived.boolean' => __('patient.validation.include_archived_boolean'),
            'archive_reason.in' => __('patient.validation.archive_reason_in'),
        ];
    }
}

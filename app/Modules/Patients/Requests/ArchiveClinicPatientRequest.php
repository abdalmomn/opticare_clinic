<?php

namespace App\Modules\Patients\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArchiveClinicPatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'archive_reason' => [
                'required',
                'in:no_longer_patient,transferred,duplicate,other',
            ],
            'archive_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'archive_reason.required' => __('patient.validation.archive_reason_required'),
            'archive_reason.in' => __('patient.validation.archive_reason_in'),
            'archive_notes.string' => __('patient.validation.archive_notes_string'),
            'archive_notes.max' => __('patient.validation.archive_notes_max'),
        ];
    }
}

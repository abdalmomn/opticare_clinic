<?php

namespace App\Modules\Patients\Requests;

use App\Modules\Patients\Enums\PatientArchiveReasonEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                // 'deceased' is excluded here; it is set via the mark-deceased endpoint.
                Rule::enum(PatientArchiveReasonEnum::class)
                    ->except([PatientArchiveReasonEnum::DECEASED]),
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

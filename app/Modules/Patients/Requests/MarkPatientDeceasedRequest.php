<?php

namespace App\Modules\Patients\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkPatientDeceasedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deceased_at' => [
                'required',
                'date',
                'before_or_equal:today',
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
            'deceased_at.required' => __('patient.validation.deceased_at_required'),
            'deceased_at.date' => __('patient.validation.deceased_at_date'),
            'deceased_at.before_or_equal' => __('patient.validation.deceased_at_before_or_equal'),
            'archive_notes.string' => __('patient.validation.archive_notes_string'),
            'archive_notes.max' => __('patient.validation.archive_notes_max'),
        ];
    }
}

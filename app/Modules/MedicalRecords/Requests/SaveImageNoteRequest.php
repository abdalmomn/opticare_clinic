<?php

namespace App\Modules\MedicalRecords\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveImageNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => 'nullable|string|max:5000',
            'visit_record_id' => 'nullable|integer|exists:visit_records,id',
        ];
    }

    public function messages(): array
    {
        return [
            'note.max' => __('medical_record.validation.note_max'),
            'visit_record_id.exists' => __('medical_record.validation.visit_invalid'),
        ];
    }
}

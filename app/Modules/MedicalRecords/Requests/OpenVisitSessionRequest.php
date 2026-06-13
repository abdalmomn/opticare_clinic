<?php

namespace App\Modules\MedicalRecords\Requests;

use App\Modules\MedicalRecords\Enums\VisitTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpenVisitSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visit_type' => ['nullable', Rule::enum(VisitTypeEnum::class)],
            'notes' => 'nullable|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'visit_type.in' => __('medical_record.validation.visit_type_invalid'),
            'notes.max' => __('medical_record.validation.notes_max'),
        ];
    }
}

<?php

namespace App\Modules\MedicalRecords\Requests;

use App\Modules\MedicalRecords\Enums\VisitTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveVisitSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visit.visit_type' => ['nullable', Rule::enum(VisitTypeEnum::class)],
            'visit.chief_complaint' => 'nullable|string|max:2000',
            'visit.symptoms' => 'nullable|string|max:2000',
            'visit.examination_notes' => 'nullable|string|max:5000',
            'visit.diagnosis' => 'nullable|string|max:5000',
            'visit.treatment_plan' => 'nullable|string|max:5000',
            'visit.notes' => 'nullable|string|max:5000',

            'eye_measurement' => 'nullable|array',
            'eye_measurement.visual_acuity_od' => 'nullable|string|max:50',
            'eye_measurement.visual_acuity_os' => 'nullable|string|max:50',
            'eye_measurement.iop_od' => 'nullable|numeric',
            'eye_measurement.iop_os' => 'nullable|numeric',
            'eye_measurement.notes' => 'nullable|string|max:5000',
            'eye_measurement.measured_at' => 'nullable|date',

            'report' => 'nullable|array',
            'report.title' => 'nullable|string|max:255',
            'report.report_text' => 'nullable|string|max:20000',
            'report.images' => 'nullable|array',
            'report.images.*.imaging_request_id' => 'nullable|integer|exists:imaging_requests,id',
            'report.images.*.imaging_file_id' => 'nullable|integer|exists:imaging_files,id',
            'report.images.*.notes' => 'nullable|string|max:2000',
            'report.selected_image_ids' => 'nullable|array',
            'report.selected_image_ids.*' => 'integer|exists:imaging_files,id',
            'report.selected_folder_ids' => 'nullable|array',
            'report.selected_folder_ids.*' => 'integer|exists:imaging_requests,id',

            'prescription' => 'nullable|array',
            'prescription.prescription_text' => 'nullable|string|max:20000',
            'prescription.notes' => 'nullable|string|max:5000',
            'prescription.items' => 'nullable|array',
            'prescription.items.*.medicine_name' => 'required_with:prescription.items|string|max:255',
            'prescription.items.*.dosage' => 'nullable|string|max:255',
            'prescription.items.*.frequency' => 'nullable|string|max:255',
            'prescription.items.*.duration' => 'nullable|string|max:255',

            'diagnosis_codes' => 'nullable|array',
            'diagnosis_codes.*' => 'integer|exists:diagnosis_codes,id',

            'private_note' => 'nullable|array',
            'private_note.note' => 'nullable|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'visit.visit_type.in' => __('medical_record.validation.visit_type_invalid'),
            'visit.chief_complaint.max' => __('medical_record.validation.chief_complaint_max'),
            'visit.symptoms.max' => __('medical_record.validation.symptoms_max'),
            'visit.examination_notes.max' => __('medical_record.validation.examination_notes_max'),
            'visit.diagnosis.max' => __('medical_record.validation.diagnosis_max'),
            'visit.treatment_plan.max' => __('medical_record.validation.treatment_plan_max'),
            'visit.notes.max' => __('medical_record.validation.notes_max'),

            'eye_measurement.visual_acuity_od.max' => __('medical_record.validation.visual_acuity_max'),
            'eye_measurement.visual_acuity_os.max' => __('medical_record.validation.visual_acuity_max'),
            'eye_measurement.iop_od.numeric' => __('medical_record.validation.iop_invalid'),
            'eye_measurement.iop_os.numeric' => __('medical_record.validation.iop_invalid'),
            'eye_measurement.measured_at.date' => __('medical_record.validation.measured_at_invalid'),

            'report.title.max' => __('medical_record.validation.report_title_max'),
            'report.report_text.max' => __('medical_record.validation.report_text_max'),
            'report.images.*.imaging_request_id.exists' => __('medical_record.validation.imaging_request_invalid'),
            'report.images.*.imaging_file_id.exists' => __('medical_record.validation.imaging_file_invalid'),

            'prescription.prescription_text.max' => __('medical_record.validation.prescription_text_max'),
            'prescription.items.*.medicine_name.required_with' => __('medical_record.validation.medicine_name_required'),
            'prescription.items.*.medicine_name.max' => __('medical_record.validation.medicine_name_max'),

            'diagnosis_codes.array' => __('medical_record.validation.diagnosis_codes_array'),
            'diagnosis_codes.*.exists' => __('medical_record.validation.diagnosis_code_invalid'),

            'private_note.note.max' => __('medical_record.validation.note_max'),
        ];
    }
}

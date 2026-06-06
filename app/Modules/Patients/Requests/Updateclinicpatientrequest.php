<?php

namespace App\Modules\Patients\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClinicPatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'      => ['sometimes', 'string', 'max:255'],
            'father_name'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'last_name'       => ['sometimes', 'string', 'max:255'],
            'identity_type'   => ['sometimes', 'in:national_id,passport'],
            'identity_number' => ['sometimes', 'string', 'max:50'],

            'gender'          => ['sometimes', 'in:male,female'],
            'date_of_birth'   => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'marital_status'  => ['sometimes', 'nullable', 'in:single,married,divorced,widowed'],
            'phone'           => ['sometimes', 'nullable', 'string', 'max:30'],
            'address'         => ['sometimes', 'nullable', 'string', 'max:1000'],

            'height_cm'       => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:300'],
            'weight_kg'       => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:500'],
            'blood_type'      => ['sometimes', 'nullable', 'string', 'max:10'],

            'is_smoker'       => ['sometimes', 'nullable', 'boolean'],
            'drinks_alcohol'  => ['sometimes', 'nullable', 'boolean'],

            'chronic_diseases'       => ['sometimes', 'nullable', 'array'],
            'diabetes_details'       => ['sometimes', 'nullable', 'array'],
            'allergies'              => ['sometimes', 'nullable', 'array'],
            'current_medications'    => ['sometimes', 'nullable', 'array'],
            'previous_eye_surgeries' => ['sometimes', 'nullable', 'array'],

            'wears_glasses_or_lenses' => ['sometimes', 'nullable', 'boolean'],
            'family_ocular_history'   => ['sometimes', 'nullable', 'string'],

            'central_user_id' => ['sometimes', 'nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.max'        => __('patient.validation.first_name_max'),
            'last_name.max'         => __('patient.validation.last_name_max'),
            'identity_type.in'      => __('patient.validation.identity_type_in'),
            'identity_number.max'   => __('patient.validation.identity_number_max'),
            'gender.in'             => __('patient.validation.gender_in'),
            'date_of_birth.date'    => __('patient.validation.date_of_birth_date'),
            'date_of_birth.before_or_equal' => __('patient.validation.date_of_birth_before_or_equal'),
            'marital_status.in'     => __('patient.validation.marital_status_in'),
            'phone.max'             => __('patient.validation.phone_max'),
            'height_cm.numeric'     => __('patient.validation.height_cm_numeric'),
            'height_cm.min'         => __('patient.validation.height_cm_min'),
            'height_cm.max'         => __('patient.validation.height_cm_max'),
            'weight_kg.numeric'     => __('patient.validation.weight_kg_numeric'),
            'weight_kg.min'         => __('patient.validation.weight_kg_min'),
            'weight_kg.max'         => __('patient.validation.weight_kg_max'),
        ];
    }
}

<?php

namespace App\Modules\Patients\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClinicPatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled in the service via AccessControlHelper.
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'      => ['required', 'string', 'max:255'],
            'father_name'     => ['nullable', 'string', 'max:255'],
            'last_name'       => ['required', 'string', 'max:255'],
            'identity_type'   => ['required', 'in:national_id,passport'],
            'identity_number' => ['required', 'string', 'max:50'],

            'gender'          => ['required', 'in:male,female'],
            'date_of_birth'   => ['nullable', 'date', 'before_or_equal:today'],
            'marital_status'  => ['nullable', 'in:single,married,divorced,widowed'],
            'phone'           => ['nullable', 'string', 'max:30'],
            'address'         => ['nullable', 'string', 'max:1000'],

            'height_cm'       => ['nullable', 'numeric', 'min:0', 'max:300'],
            'weight_kg'       => ['nullable', 'numeric', 'min:0', 'max:500'],
            'blood_type'      => ['nullable', 'string', 'max:10'],

            'is_smoker'       => ['nullable', 'boolean'],
            'drinks_alcohol'  => ['nullable', 'boolean'],

            'chronic_diseases'       => ['nullable', 'array'],
            'diabetes_details'       => ['nullable', 'array'],
            'allergies'              => ['nullable', 'array'],
            'current_medications'    => ['nullable', 'array'],
            'previous_eye_surgeries' => ['nullable', 'array'],

            'wears_glasses_or_lenses' => ['nullable', 'boolean'],
            'family_ocular_history'   => ['nullable', 'string'],

            'central_user_id'     => ['nullable', 'integer'],
            'medical_file_number' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required'      => __('patient.validation.first_name_required'),
            'first_name.max'           => __('patient.validation.first_name_max'),
            'last_name.required'       => __('patient.validation.last_name_required'),
            'last_name.max'            => __('patient.validation.last_name_max'),
            'identity_type.required'   => __('patient.validation.identity_type_required'),
            'identity_type.in'         => __('patient.validation.identity_type_in'),
            'identity_number.required' => __('patient.validation.identity_number_required'),
            'identity_number.max'      => __('patient.validation.identity_number_max'),
            'gender.required'          => __('patient.validation.gender_required'),
            'gender.in'                => __('patient.validation.gender_in'),
            'date_of_birth.date'       => __('patient.validation.date_of_birth_date'),
            'date_of_birth.before_or_equal' => __('patient.validation.date_of_birth_before_or_equal'),
            'marital_status.in'        => __('patient.validation.marital_status_in'),
            'phone.max'                => __('patient.validation.phone_max'),
            'height_cm.numeric'        => __('patient.validation.height_cm_numeric'),
            'height_cm.min'            => __('patient.validation.height_cm_min'),
            'height_cm.max'            => __('patient.validation.height_cm_max'),
            'weight_kg.numeric'        => __('patient.validation.weight_kg_numeric'),
            'weight_kg.min'            => __('patient.validation.weight_kg_min'),
            'weight_kg.max'            => __('patient.validation.weight_kg_max'),
        ];
    }
}

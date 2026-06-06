<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Patient Module – English Translations
    |--------------------------------------------------------------------------
    */

    'messages' => [
        'patients_fetched'      => 'Patients fetched successfully.',
        'patient_fetched'       => 'Patient file fetched successfully.',
        'patient_created'       => 'Patient file created successfully.',
        'patient_updated'       => 'Patient file updated successfully.',
        'patient_status_updated'=> 'Patient status updated successfully.',
        'patient_archived' => 'Patient file archived successfully.',
        'patient_restored' => 'Patient file restored successfully.',
        'patient_marked_deceased' => 'Patient file marked as deceased successfully.',
    ],

    'errors' => [
        'not_allowed_view'   => 'You are not allowed to view patients.',
        'not_allowed_search' => 'You are not allowed to search patients.',
        'not_allowed_create' => 'You are not allowed to create patients.',
        'not_allowed_edit'   => 'You are not allowed to edit patients.',
        'identity_exists'    => 'A patient with this national ID or passport number already exists.',
        'patient_not_found'  => 'Patient file not found.',
        'not_allowed_archive' => 'You are not allowed to archive patients.',
        'not_allowed_restore' => 'You are not allowed to restore patients.',
        'patient_already_archived' => 'Patient file is already archived.',
        'patient_is_not_archived' => 'Patient file is not archived.',
        'patient_already_deceased' => 'Patient file is already marked as deceased.',
        'deceased_patient_cannot_be_archived' => 'A deceased patient cannot be archived again.',
        'deceased_patient_cannot_be_restored' => 'A deceased patient cannot be restored.',
        'patient_status_cannot_be_toggled' => 'Archived or deceased patient status cannot be toggled.',
    ],

    'validation' => [
        'first_name_required' => 'First name is required.',
        'first_name_max'      => 'First name may not be greater than 255 characters.',
        'last_name_required'  => 'Last name is required.',
        'last_name_max'       => 'Last name may not be greater than 255 characters.',

        'identity_type_required'   => 'Identity type is required.',
        'identity_type_in'         => 'Identity type must be either national_id or passport.',
        'identity_number_required' => 'Identity number is required.',
        'identity_number_max'      => 'Identity number may not be greater than 50 characters.',
        'identity_number_unique'   => 'A patient with this identity number already exists.',

        'gender_required'   => 'Gender is required.',
        'gender_in'         => 'Gender must be either male or female.',
        'marital_status_in' => 'Marital status must be one of: single, married, divorced, widowed.',
        'phone_max'         => 'Phone number may not be greater than 30 characters.',

        'date_of_birth_date'             => 'Date of birth must be a valid date.',
        'date_of_birth_before_or_equal'  => 'Date of birth must not be in the future.',

        'height_cm_numeric' => 'Height must be a numeric value.',
        'height_cm_min'     => 'Height must be at least 0 cm.',
        'height_cm_max'     => 'Height may not exceed 300 cm.',
        'weight_kg_numeric' => 'Weight must be a numeric value.',
        'weight_kg_min'     => 'Weight must be at least 0 kg.',
        'weight_kg_max'     => 'Weight may not exceed 500 kg.',

        'keyword_max'      => 'Search keyword may not be greater than 255 characters.',
        'per_page_integer' => 'Per page must be an integer.',
        'per_page_min'     => 'Per page must be at least 1.',
        'per_page_max'     => 'Per page may not exceed 100.',

        'status_in' => 'Status must be active, inactive, archived, or deceased.',
        'include_archived_boolean' => 'Include archived must be true or false.',
        'archive_reason_required' => 'Archive reason is required.',
        'archive_reason_in' => 'Archive reason is not valid.',
        'archive_notes_string' => 'Archive notes must be text.',
        'archive_notes_max' => 'Archive notes may not be greater than 2000 characters.',
        'deceased_at_required' => 'Date of death is required.',
        'deceased_at_date' => 'Date of death must be a valid date.',
        'deceased_at_before_or_equal' => 'Date of death cannot be in the future.',
    ],

];
